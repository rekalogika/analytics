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

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Core\GroupingStrategy\ExplicitGroupingSetsStrategy;
use Rekalogika\Analytics\Metadata\Groupings\DefaultGroupByExpressions;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final class ExplicitGroupingSetsStrategyTest extends TestCase
{
    private function getFields(): DefaultGroupByExpressions
    {
        return new DefaultGroupByExpressions([
            'time' => new RollUp(new Field('year'), new Field('month'), new Field('day')),
            'company' => new Field('company'),
            'status' => new Field('status'),
            'location' => new FieldSet(new Field('country'), new Field('city')),
            'product' => new Cube(new Field('type'), new Field('size')),
        ]);
    }

    /**
     * @return list<list<string>>
     */
    private static function toArray(GroupingSet $groupingSet): array
    {
        $result = [];

        foreach ($groupingSet as $fieldSet) {
            self::assertInstanceOf(FieldSet::class, $fieldSet);

            $result[] = array_map(
                static fn(Field $field): string => $field->getContent(),
                $fieldSet->toArray(),
            );
        }

        return $result;
    }

    public function testPlainFields(): void
    {
        $strategy = new ExplicitGroupingSetsStrategy([
            ['company', 'status'],
            ['status'],
        ]);

        $this->assertSame(
            [['company', 'status'], ['status']],
            self::toArray($strategy->getGroupByExpression($this->getFields())),
        );
    }

    public function testRollUpMemberKeepsItsSemantics(): void
    {
        $strategy = new ExplicitGroupingSetsStrategy([
            ['time', 'company'],
        ]);

        $this->assertSame(
            [
                ['year', 'month', 'day', 'company'],
                ['year', 'month', 'company'],
                ['year', 'company'],
                ['company'],
            ],
            self::toArray($strategy->getGroupByExpression($this->getFields())),
        );
    }

    public function testFieldSetAndCubeMembers(): void
    {
        $strategy = new ExplicitGroupingSetsStrategy([
            ['location', 'product'],
        ]);

        $result = self::toArray($strategy->getGroupByExpression($this->getFields()));

        $this->assertCount(4, $result);
        $this->assertContains(['country', 'city', 'type', 'size'], $result);
        $this->assertContains(['country', 'city', 'size'], $result);
        $this->assertContains(['country', 'city', 'type'], $result);
        $this->assertContains(['country', 'city'], $result);
    }

    public function testDuplicateSetsAreRemovedRegardlessOfOrder(): void
    {
        $strategy = new ExplicitGroupingSetsStrategy([
            ['company', 'status'],
            ['status', 'company'],
            ['time'],
            [],
        ]);

        $result = self::toArray($strategy->getGroupByExpression($this->getFields()));

        $this->assertSame(
            [
                ['company', 'status'],
                ['year', 'month', 'day'],
                ['year', 'month'],
                ['year'],
                [],
            ],
            $result,
        );
    }

    public function testEmptySetIsGrandTotal(): void
    {
        $strategy = new ExplicitGroupingSetsStrategy([[]]);

        $this->assertSame(
            [[]],
            self::toArray($strategy->getGroupByExpression($this->getFields())),
        );
    }

    public function testUnknownName(): void
    {
        $strategy = new ExplicitGroupingSetsStrategy([['company', 'foo']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown dimension "foo"');
        $strategy->getGroupByExpression($this->getFields());
    }

    public function testNoSets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExplicitGroupingSetsStrategy([]);
    }

    public function testDuplicateNamesInOneSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExplicitGroupingSetsStrategy([['company', 'company']]);
    }
}
