<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/analytics package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Engine\Groupings\GroupingSetSelector;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final class GroupingSetSelectorTest extends TestCase
{
    /**
     * The default summary: cross product of a cube of each plain dimension
     * and the dimension groups.
     */
    private static function getCubeGroupBy(): GroupBy
    {
        return new GroupBy(
            new Cube(new Field('a')),
            new Cube(new Field('b')),
            new RollUp(new Field('year'), new Field('month'), new Field('day')),
            new GroupingSet(
                new FieldSet(new Field('c1'), new Field('c2')),
                new FieldSet(new Field('d')),
                new FieldSet(),
            ),
        );
    }

    /**
     * Explicit grouping sets.
     */
    private static function getExplicitGroupBy(): GroupBy
    {
        return new GroupBy(
            new GroupingSet(
                new FieldSet(new Field('a'), new Field('b'), new Field('c')),
                new FieldSet(new Field('a')),
                new FieldSet(new Field('b'), new Field('d')),
            ),
        );
    }

    /**
     * @param list<string> $fields
     * @param list<string>|null $expected
     */
    #[DataProvider('provideSelect')]
    public function testSelect(GroupBy $groupBy, array $fields, ?array $expected): void
    {
        $result = (new GroupingSetSelector($groupBy))->select($fields);

        if ($result !== null) {
            sort($result);
        }

        if ($expected !== null) {
            sort($expected);
        }

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string,array{GroupBy,list<string>,list<string>|null}>
     */
    public static function provideSelect(): iterable
    {
        yield 'cube: nothing' => [self::getCubeGroupBy(), [], []];
        yield 'cube: plain fields' => [self::getCubeGroupBy(), ['a', 'b'], ['a', 'b']];
        yield 'cube: rollup includes ancestors' => [self::getCubeGroupBy(), ['month'], ['year', 'month']];
        yield 'cube: field set siblings' => [self::getCubeGroupBy(), ['c2', 'a'], ['a', 'c1', 'c2']];
        yield 'cube: mixed' => [self::getCubeGroupBy(), ['day', 'd'], ['year', 'month', 'day', 'd']];
        yield 'cube: alternatives of a grouping set cannot be combined' => [self::getCubeGroupBy(), ['c1', 'd'], null];
        yield 'cube: unknown field' => [self::getCubeGroupBy(), ['x'], null];

        yield 'explicit: exact set' => [self::getExplicitGroupBy(), ['a'], ['a']];
        yield 'explicit: smallest superset' => [self::getExplicitGroupBy(), ['b'], ['b', 'd']];
        yield 'explicit: superset' => [self::getExplicitGroupBy(), ['a', 'b'], ['a', 'b', 'c']];
        yield 'explicit: nothing selects the smallest set' => [self::getExplicitGroupBy(), [], ['a']];
        yield 'explicit: not available' => [self::getExplicitGroupBy(), ['c', 'd'], null];
    }

    public function testCountAndSets(): void
    {
        $selector = new GroupingSetSelector(self::getCubeGroupBy());

        // 2 * 2 * 4 * 3
        $this->assertSame(48, $selector->count());
        $this->assertCount(5, $selector->getSets(5));
        $this->assertSame(
            ['a', 'b', 'year', 'month', 'day', 'c1', 'c2'],
            $selector->getSets(1)[0],
        );

        $selector = new GroupingSetSelector(self::getExplicitGroupBy());

        $this->assertSame(3, $selector->count());
        $this->assertSame(
            [['a', 'b', 'c'], ['a'], ['b', 'd']],
            $selector->getSets(10),
        );
    }
}
