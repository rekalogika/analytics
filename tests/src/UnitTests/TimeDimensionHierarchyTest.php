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
use Rekalogika\Analytics\TimeDimensionHierarchy\Interval;
use Rekalogika\Analytics\TimeDimensionHierarchy\Month;
use Rekalogika\Analytics\TimeDimensionHierarchy\Year;

class TimeDimensionHierarchyTest extends TestCase
{
    /**
     * @dataProvider intervalProvider
     * @param class-string<Interval> $class
     */
    public function testInterval(
        int $databaseInput,
        string $class,
        \DateTimeInterface $expectedStart,
        \DateTimeInterface $expectedEnd,
    ): void {
        $interval = $class::createFromDatabaseValue(
            databaseValue: $databaseInput,
            timeZone: new \DateTimeZone('UTC'),
        );

        $start = $interval->getStart();
        $end = $interval->getEnd();

        $this->assertEquals($expectedStart, $start);
        $this->assertEquals($expectedEnd, $end);
    }

    /**
     * @return iterable<array-key,array{int,class-string<Interval>,\DateTimeInterface,\DateTimeInterface}>
     */
    public static function intervalProvider(): iterable
    {
        yield [
            2022,
            Year::class,
            new \DateTimeImmutable('2022-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
        ];

        yield [
            202202,
            Month::class,
            new \DateTimeImmutable('2022-02-01T00:00:00+00:00'),
            new \DateTimeImmutable('2022-03-01T00:00:00+00:00'),
        ];
    }
}
