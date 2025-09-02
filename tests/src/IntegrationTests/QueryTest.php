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

namespace Rekalogika\Analytics\Tests\IntegrationTests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Exception\OverflowException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerType;
use Rekalogika\Analytics\Tests\App\Entity\Gender;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Time\Bin\Gregorian\DayOfMonth;
use Rekalogika\Analytics\Time\Bin\Gregorian\Hour;
use Rekalogika\Analytics\Time\Bin\Gregorian\Month;
use Rekalogika\Analytics\Time\Bin\Gregorian\MonthOfYear;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class QueryTest extends KernelTestCase
{
    private function getQuery(
        ?int $queryResultLimit = null,
    ): Query {
        $summaryManager = static::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(DefaultSummaryManager::class, $summaryManager);

        /** @psalm-suppress InvalidNamedArgument */
        return $summaryManager
            ->createQuery(queryResultLimit: $queryResultLimit)
            ->from(OrderSummary::class);
    }

    public function testNoDimensionAndOneMeasure(): void
    {
        $cell = $this->getQuery()
            ->getResult()
            ->getCube()
            ->find('@values', 'count');

        $this->assertNotNull($cell);
        $this->assertIsInt($cell->getMeasure()->getValue());
    }

    public function testInvalidDimension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getQuery()
            ->withDimensions('invalid')
            ->getResult()
            ->getCube();
    }

    public function testTraversal(): void
    {
        // get a valid country
        $country = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?->getCountry();

        $this->assertNotNull($country);

        $cell = $this->getQuery()
            ->withDimensions('time.civil.year', 'customerCountry')
            ->getResult()
            ->getCube();

        $cell2 = $cell
            ->find('time.civil.year', '2024')
            ?->find('customerCountry', $country)
            ?->find('@values', 'count');

        //  stringable check
        $cell4 = $cell
            ->find('time.civil.year', '2024')
            ?->find('customerCountry', $country->getName())
            ?->find('@values', 'count');

        $this->assertSame($cell2, $cell4);
    }

    public function testGroupByValueType(): void
    {
        // get a valid country
        $country = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?->getCountry();

        $this->assertNotNull($country);

        $result = $this->getQuery()
            ->withDimensions('time.civil.year', '@values', 'customerCountry')
            ->getResult()
            ->getCube()
            ->find('time.civil.year', '2024')
            ?->find('@values', 'count')
            ?->find('customerCountry', $country);

        $this->assertIsInt($result?->getMeasure()->getValue());
    }

    public function testGroupByValueTypeFirst(): void
    {
        // get a valid country
        $country = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?->getCountry();

        $this->assertNotNull($country);

        $result = $this->getQuery()
            ->withDimensions('@values', 'time.civil.year', 'customerCountry')
            ->getResult()
            ->getCube();

        $node = $result
            ->find('@values', 'count')
            ?->find('time.civil.year', '2024')
            ?->find('customerCountry', $country->getName());

        $this->assertIsInt($node?->getMeasure()?->getValue());
    }

    public function testGroupByValueTypeLast(): void
    {
        // get a valid country
        $country = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?->getCountry();

        $this->assertNotNull($country);

        $result = $this->getQuery()
            ->withDimensions('time.civil.year', 'customerCountry', '@values')
            ->getResult()
            ->getCube();

        $node = $result
            ->find('time.civil.year', '2024')
            ?->find('customerCountry', $country->getName())
            ?->find('@values', 'count');

        $this->assertIsInt($node?->getMeasure()?->getValue());
    }

    public function testSlice(): void
    {
        $year = 2024;

        $result = $this->getQuery()
            ->withDimensions('time.civil.year')
            ->getResult()
            ->getCube();

        $count = $result
            ->slice('time.civil.year', $year)
            ?->getMeasures()
            ->get('count')
            ?->getValue();

        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testDrillDown(): void
    {
        $year = 2024;

        $result = $this->getQuery()
            ->withDimensions('time.civil.year')
            ->getResult()
            ->getCube();

        $cells = $result
            ->drillDown('time.civil.year');

        $this->assertGreaterThan(0, $cells->count());

        foreach ($cells as $cell) {
            if ($cell->getCoordinates()->get('time.civil.year')?->getMember() === $year) {
                $count = $cell->getMeasures()->get('count')?->getValue();
                $this->assertIsInt($count);
                $this->assertGreaterThan(0, $count);
                return;
            }
        }
    }

    public function testRollUp(): void
    {
        // get a valid country
        $country = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?->getCountry();

        $year = 2024;

        $result = $this->getQuery()
            ->withDimensions('time.civil.year', 'customerCountry')
            ->getResult()
            ->getCube();

        $cell = $result
            ->slice('time.civil.year', 2023)
            ?->slice('customerCountry', $country);

        $this->assertNotNull($cell);

        $rolledUp = $cell->rollUp(['time.civil.year', 'customerCountry']);

        /** @psalm-suppress MixedAssignment */
        $count = $rolledUp->getMeasures()->get('count')?->getValue();
        $this->assertEquals(190, $count);
    }

    public function testDrillDownMulti(): void
    {
        $year = 2024;

        $result = $this->getQuery()
            ->withDimensions('time.civil.year', 'customerCountry')
            ->getResult()
            ->getCube();

        $cells = $result
            ->drillDown(['time.civil.year', 'customerCountry']);

        $this->assertGreaterThan(0, $cells->count());

        foreach ($cells as $cell) {
            if ($cell->getCoordinates()->get('time.civil.year')?->getMember() === $year) {
                $count = $cell->getMeasures()->get('count')?->getValue();
                $this->assertIsInt($count);
                $this->assertGreaterThan(0, $count);
                return;
            }
        }
    }

    public function testWhere(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.year')
            ->dice(Criteria::expr()->eq('time.civil.year', 2024))
            ->getResult()
            ->getCube();

        $count = $result
            ->find('time.civil.year', '2024')
            ?->find('@values', 'count')
            ?->getMeasure()
            ?->getValue();

        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereMonthObject(): void
    {
        $month = Month::createFromDatabaseValue(202410);

        $result = $this->getQuery()
            ->withDimensions('time.civil.month.month')
            ->dice(Criteria::expr()->eq('time.civil.month.month', $month))
            ->getResult()
            ->getCube()
            ->find('time.civil.month.month', '2024-10');

        $this->assertNotNull($result);
    }

    public function testWhereMonthObjectIn(): void
    {
        $months = [
            Month::createFromDatabaseValue(202410),
            Month::createFromDatabaseValue(202411),
            Month::createFromDatabaseValue(202412),
        ];

        $cube = $this->getQuery()
            ->withDimensions('time.civil.month.month')
            ->dice(Criteria::expr()->in('time.civil.month.month', $months))
            ->getResult()
            ->getCube();

        $this->assertNotNull($cube->find('time.civil.month.month', '2024-10'));
        $this->assertNotNull($cube->find('time.civil.month.month', '2024-11'));
        $this->assertNotNull($cube->find('time.civil.month.month', '2024-12'));
    }

    public function testWhereMonthDatabaseValue(): void
    {
        $month = 202410;

        $result = $this->getQuery()
            ->withDimensions('time.civil.month.month')
            ->dice(Criteria::expr()->eq('time.civil.month.month', $month))
            ->getResult()
            ->getCube()
            ->find('time.civil.month.month', '2024-10');

        $this->assertNotNull($result);
    }

    public function testWhereWithOr(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.year')
            ->dice(Criteria::expr()->orX(
                Criteria::expr()->eq('time.civil.year', 2024),
                Criteria::expr()->eq('time.civil.year', 2023),
            ))
            ->getResult()
            ->getCube();

        // Test that we can access both years' data
        $count2023 = $result->find('time.civil.year', '2023')
            ?->find('@values', 'count')
            ?->getMeasure()
            ?->getValue();
        $count2024 = $result->find('time.civil.year', '2024')
            ?->find('@values', 'count')
            ?->getMeasure()
            ?->getValue();

        $this->assertIsInt($count2023);
        $this->assertGreaterThan(0, $count2023);
        $this->assertIsInt($count2024);
        $this->assertGreaterThan(0, $count2024);
    }

    public function testWhereWithDimensionNotInGroupBy(): void
    {
        $all = $this->getQuery()
            ->getResult()
            ->getCube()
            ->find('@values', 'count')
            ?->getMeasure()?->getValue();

        $this->assertNotNull($all);

        $withWhere = $this->getQuery()
            ->dice(Criteria::expr()->eq('time.civil.year', 2024))
            ->getResult()
            ->getCube()
            ->find('@values', 'count')
            ?->getMeasure()?->getValue();

        $this->assertNotNull($withWhere);
        $this->assertLessThan($all, $withWhere);
    }


    public function testOrderByMeasureOnlyGetTable(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.year')
            ->orderBy('count', Order::Descending)
            ->getResult()
            ->getCube()
            ->drillDown('time.civil.year');

        $this->assertCount(2, $result);
    }

    public function testQueryResultLimit(): void
    {
        $this->expectException(OverflowException::class);

        $result = $this->getQuery(queryResultLimit: 1)
            ->withDimensions('time.civil.hour.hour')
            ->getResult();

        // This should trigger the overflow exception when getting the cube
        $cube = $result->getCube();
    }

    public function testWhereWithTimeBin(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.hour.hour')
            ->dice(Criteria::expr()->gte(
                'time.civil.hour.hour',
                Hour::createFromDatabaseValue(2024101010),
            ))
            ->getResult()
            ->getCube()
            ->drillDown('time.civil.hour.hour');

        $this->assertGreaterThan(1, $result->count());
    }

    public function testWhereWithTimeBinRange(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.hour.hour')
            ->dice(Criteria::expr()->andX(
                Criteria::expr()->gte(
                    'time.civil.hour.hour',
                    Hour::createFromDatabaseValue(2024101010),
                ),
                Criteria::expr()->lte(
                    'time.civil.hour.hour',
                    Hour::createFromDatabaseValue(2024111011),
                ),
            ))
            ->getResult()
            ->getCube()
            ->drillDown('time.civil.hour.hour');

        $this->assertGreaterThan(1, $result->count());
    }

    public function testWhereWithRecurringTimeBinRangeEnum(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.date.dayOfMonth')
            ->dice(Criteria::expr()->andX(
                Criteria::expr()->gte('time.civil.date.dayOfMonth', DayOfMonth::Day10),
                Criteria::expr()->lte('time.civil.date.dayOfMonth', DayOfMonth::Day12),
            ))
            ->getResult()
            ->getCube()
            ->drillDown('time.civil.date.dayOfMonth');

        $this->assertGreaterThan(0, $result->count());
    }

    public function testWhereWithRecurringTimeBinRangeInteger(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.date.dayOfMonth')
            ->dice(Criteria::expr()->andX(
                Criteria::expr()->gte('time.civil.date.dayOfMonth', 10),
                Criteria::expr()->lte('time.civil.date.dayOfMonth', 12),
            ))
            ->getResult()
            ->getCube()
            ->drillDown('time.civil.date.dayOfMonth');

        $this->assertGreaterThan(0, $result->count());
    }

    public function testYearByMonthOfYear(): void
    {
        $result = $this->getQuery()
            ->withDimensions('time.civil.year', 'time.civil.month.monthOfYear')
            ->getResult()
            ->getCube();

        // Test that we can access data for both years and various months
        $year2023Data = $result->find('time.civil.year', '2023');
        $this->assertNotNull($year2023Data);

        $year2024Data = $result->find('time.civil.year', '2024');
        $this->assertNotNull($year2024Data);

        // Test a few specific month combinations to ensure the data structure works
        foreach ([MonthOfYear::January, MonthOfYear::June, MonthOfYear::December] as $monthOfYear) {
            $count2023 = $year2023Data->find('time.civil.month.monthOfYear', $monthOfYear)
                ?->find('@values', 'count')
                ?->getMeasure();
            $this->assertNotNull($count2023, "Expected data for 2023 " . $monthOfYear->name);

            $count2024 = $year2024Data->find('time.civil.month.monthOfYear', $monthOfYear)
                ?->find('@values', 'count')
                ?->getMeasure();
            $this->assertNotNull($count2024, "Expected data for 2024 " . $monthOfYear->name);
        }
    }

    public function testWhereWithEnumCondition(): void
    {
        $result = $this->getQuery()
            ->dice(Criteria::expr()->eq(
                'customerType',
                CustomerType::Individual,
            ))
            ->getResult()
            ->getCube();

        $count = $result->find('@values', 'count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithEnumInCondition(): void
    {
        $result = $this->getQuery()
            ->dice(Criteria::expr()->in(
                'customerType',
                [CustomerType::Individual],
            ))
            ->getResult()
            ->getCube();

        $count = $result->find('@values', 'count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithNullCondition(): void
    {
        $result = $this->getQuery()
            ->dice(Criteria::expr()->eq(
                'customerGender',
                null,
            ))
            ->getResult()
            ->getCube();

        $count = $result->find('@values', 'count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithInNullCondition(): void
    {
        $result = $this->getQuery()
            ->dice(Criteria::expr()->in(
                'customerGender',
                [null],
            ))
            ->getResult()
            ->getCube();

        $count = $result->find('@values', 'count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithInItemAndNullCondition(): void
    {
        $result = $this->getQuery()
            ->dice(Criteria::expr()->in(
                'customerGender',
                [null, Gender::Female],
            ))
            ->getResult()
            ->getCube();

        $count = $result->find('@values', 'count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithEmptyIn(): void
    {
        $result = $this->getQuery()
            ->dice(Criteria::expr()->in(
                'customerGender',
                [],
            ))
            ->getResult()
            ->getCube();

        $count = $result->find('@values', 'count')?->getMeasure()?->getValue();
        $this->assertNull($count);
    }
}
