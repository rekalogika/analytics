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
use Rekalogika\Analytics\Contracts\Exception\HierarchicalOrderingRequired;
use Rekalogika\Analytics\Contracts\Exception\OverflowException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultRow;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTable;
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

    public function testEmptyQuery(): void
    {
        $result = $this->getQuery()->getResult()->getTree();
        $this->assertCount(0, $result);
    }

    public function testDimensionWithoutMeasure(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.year')
            ->getResult()
            ->getTree();

        $this->assertCount(0, $result);
    }

    public function testNoDimensionAndOneMeasure(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);

        $node = $result->traverse('count');
        $this->assertNotNull($node);
        $this->assertEquals('@values', $node->getName());
        $this->assertIsInt($node->getMeasure()?->getValue());
    }

    public function testInvalidDimension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getQuery()
            ->groupBy('invalid')
            ->getResult()
            ->getTree();
    }

    public function testInvalidMeasure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getQuery()
            ->select('invalid')
            ->getResult()
            ->getTree();
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

        $result = $this->getQuery()
            ->groupBy('time.civil.year', 'customerCountry')
            ->select('count', 'price')
            ->getResult()
            ->getTree();

        $this->assertCount(2, $result);

        // single traverse
        $node1 = $result->traverse('2024', $country, 'count');
        $this->assertNotNull($node1);
        $this->assertIsInt($node1->getMeasure()?->getValue());

        // multistep traverse
        $node2 = $result
            ->traverse('2024')
            ?->traverse($country)
            ?->traverse('count');

        $this->assertSame($node1, $node2);

        // single traverse with stringable check
        $node3 = $result->traverse('2024', $country->getName(), 'count');
        $this->assertSame($node1, $node3);

        // multistep traverse with stringable check
        $node4 = $result
            ->traverse('2024')
            ?->traverse($country->getName())
            ?->traverse('count');

        $this->assertSame($node1, $node4);
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
            ->groupBy('time.civil.year', '@values', 'customerCountry')
            ->select('count', 'price')
            ->getResult()
            ->getTree();

        $node = $result->traverse('2024', 'count', $country->getName());
        $this->assertIsInt($node?->getMeasure()?->getValue());
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
            ->groupBy('@values', 'time.civil.year', 'customerCountry')
            ->select('count', 'price')
            ->getResult()
            ->getTree();

        $node = $result->traverse('count', '2024', $country->getName());
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
            ->groupBy('time.civil.year', 'customerCountry', '@values')
            ->select('count', 'price')
            ->getResult()
            ->getTree();

        $node = $result->traverse('2024', $country->getName(), 'count');
        $this->assertIsInt($node?->getMeasure()?->getValue());
    }

    public function testWhere(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.year')
            ->select('count')
            ->where(Criteria::expr()->eq('time.civil.year', 2024))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
    }

    public function testWhereMonthObject(): void
    {
        $month = Month::createFromDatabaseValue(202410);

        $result = $this->getQuery()
            ->groupBy('time.civil.month.month')
            ->select('count')
            ->where(Criteria::expr()->eq('time.civil.month.month', $month))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
    }

    public function testWhereMonthObjectIn(): void
    {
        $months = [
            Month::createFromDatabaseValue(202410),
            Month::createFromDatabaseValue(202411),
            Month::createFromDatabaseValue(202412),
        ];

        $result = $this->getQuery()
            ->groupBy('time.civil.month.month')
            ->select('count')
            ->where(Criteria::expr()->in('time.civil.month.month', $months))
            ->getResult()
            ->getTree();

        $this->assertCount(3, $result);
    }

    public function testWhereMonthDatabaseValue(): void
    {
        $month = 202410;

        $result = $this->getQuery()
            ->groupBy('time.civil.month.month')
            ->select('count')
            ->where(Criteria::expr()->eq('time.civil.month.month', $month))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
    }

    public function testWhereWithOr(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.year')
            ->select('count')
            ->where(Criteria::expr()->orX(
                Criteria::expr()->eq('time.civil.year', 2024),
                Criteria::expr()->eq('time.civil.year', 2023),
            ))
            ->getResult()
            ->getTree();

        $this->assertCount(2, $result);
    }

    public function testWhereWithDimensionNotInGroupBy(): void
    {
        $all = $this->getQuery()
            ->select('count')
            ->getResult()
            ->getTree()
            ->traverse('count')
            ?->getMeasure()?->getValue();

        $this->assertNotNull($all);

        $withWhere = $this->getQuery()
            ->select('count')
            ->where(Criteria::expr()->eq('time.civil.year', 2024))
            ->getResult()
            ->getTree()
            ->traverse('count')
            ?->getMeasure()?->getValue();

        $this->assertNotNull($withWhere);

        $this->assertLessThan($all, $withWhere);
    }

    public function testOrderByDimensionAscending(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.month.month')
            ->select('count')
            ->orderBy('time.civil.month.month', Order::Ascending)
            ->getResult()
            ->getTree();

        $months = [];

        foreach ($result as $node) {
            /** @psalm-suppress MixedAssignment */
            $month = $node->getMember();

            $this->assertInstanceOf(Month::class, $month);
            $months[] = (string) $month;
        }

        // assert that the months are sorted in descending order
        $sorted = $months;
        sort($sorted);

        $this->assertEquals($sorted, $months);
    }

    public function testOrderByDimensionDescending(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.month.month')
            ->select('count')
            ->orderBy('time.civil.month.month', Order::Descending)
            ->getResult();

        $result = $result->getTree();

        $months = [];

        foreach ($result as $node) {
            /** @psalm-suppress MixedAssignment */
            $month = $node->getMember();

            $this->assertInstanceOf(Month::class, $month);
            $months[] = (string) $month;
        }

        // assert that the months are sorted in descending order
        $sorted = $months;
        rsort($sorted);

        $this->assertEquals($sorted, $months);
    }

    public function testOrderByDimensionNonHierarchical(): void
    {
        $this->expectException(HierarchicalOrderingRequired::class);

        $result = $this->getQuery()
            ->groupBy('time.civil.month.month', 'customerType')
            ->select('count')
            ->orderBy('customerType', Order::Descending)
            ->addOrderBy('time.civil.month.month', Order::Descending)
            ->getResult()
            ->getTree();
    }

    public function testOrderByDimensionHierarchical(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.year', 'customerType')
            ->select('count')
            ->orderBy('time.civil.year', Order::Descending)
            ->addOrderBy('customerType', Order::Descending)
            ->getResult()
            ->getTree();

        $this->assertCount(2, $result);
    }

    public function testOrderByMeasureOnlyGetTree(): void
    {
        $this->expectException(HierarchicalOrderingRequired::class);

        $result = $this->getQuery()
            ->groupBy('time.civil.month.month')
            ->select('count')
            ->orderBy('count', Order::Descending)
            ->getResult()
            ->getTree();
    }

    public function testOrderByMeasureOnlyGetTable(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.year')
            ->select('count')
            ->orderBy('count', Order::Descending)
            ->getResult()
            ->getTable();

        $this->assertCount(2, $result);
    }

    public function testQueryResultLimit(): void
    {
        $this->expectException(OverflowException::class);

        $result = $this->getQuery(queryResultLimit: 1)
            ->groupBy('time.civil.hour.hour')
            ->select('count')
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testWhereWithTimeBin(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.hour.hour')
            ->select('count')
            ->where(Criteria::expr()->gte(
                'time.civil.hour.hour',
                Hour::createFromDatabaseValue(2024101010),
            ))
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testWhereWithTimeBinRange(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.hour.hour')
            ->select('count')
            ->where(Criteria::expr()->andX(
                Criteria::expr()->gte(
                    'time.civil.hour.hour',
                    Hour::createFromDatabaseValue(2024101010),
                ),
                Criteria::expr()->lte(
                    'time.civil.hour.hour',
                    Hour::createFromDatabaseValue(2024101011),
                ),
            ))
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testWhereWithRecurringTimeBinRangeEnum(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.date.dayOfMonth')
            ->select('count')
            ->where(Criteria::expr()->andX(
                Criteria::expr()->gte('time.civil.date.dayOfMonth', DayOfMonth::Day10),
                Criteria::expr()->lte('time.civil.date.dayOfMonth', DayOfMonth::Day12),
            ))
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testWhereWithRecurringTimeBinRangeInteger(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.date.dayOfMonth')
            ->select('count')
            ->where(Criteria::expr()->andX(
                Criteria::expr()->gte('time.civil.date.dayOfMonth', 10),
                Criteria::expr()->lte('time.civil.date.dayOfMonth', 12),
            ))
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testYearByMonthOfYear(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.year', 'time.civil.month.monthOfYear')
            ->select('count')
            ->getResult()
            ->getTree();

        $year2023 = $result->traverse('2023');
        $this->assertNotNull($year2023);
        $this->assertCount(12, $year2023);

        foreach ($year2023 as $month) {
            $this->assertInstanceOf(MonthOfYear::class, $month->getMember());
            $this->assertNotNull($month->traverse('count')?->getMeasure());
        }

        $year2024 = $result->traverse('2024');
        $this->assertNotNull($year2024);
        $this->assertCount(12, $year2024);

        foreach ($year2024 as $month) {
            $this->assertInstanceOf(MonthOfYear::class, $month->getMember());
            $this->assertNotNull($month->traverse('count')?->getMeasure());
        }
    }

    public function testWhereWithEnumCondition(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->where(Criteria::expr()->eq(
                'customerType',
                CustomerType::Individual,
            ))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
        $count = $result->traverse('count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithEnumInCondition(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->where(Criteria::expr()->in(
                'customerType',
                [CustomerType::Individual],
            ))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
        $count = $result->traverse('count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithNullCondition(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->where(Criteria::expr()->eq(
                'customerGender',
                null,
            ))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
        $count = $result->traverse('count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithInNullCondition(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->where(Criteria::expr()->in(
                'customerGender',
                [null],
            ))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
        $count = $result->traverse('count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithInItemAndNullCondition(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->where(Criteria::expr()->in(
                'customerGender',
                [null, Gender::Female],
            ))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
        $count = $result->traverse('count')?->getMeasure()?->getValue();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testWhereWithEmptyIn(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->where(Criteria::expr()->in(
                'customerGender',
                [],
            ))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
        $count = $result->traverse('count')?->getMeasure()?->getValue();
        $this->assertNull($count);
    }

    /**
     * @todo complete this test
     */
    public function testTableSubtotal(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.civil.year', 'customerType', 'customerGender')
            ->select('count')
            ->getResult()
            ->getTable();

        $this->assertInstanceOf(DefaultTable::class, $result);

        $rows = iterator_to_array($result);
        $first = array_shift($rows);

        $this->assertInstanceOf(DefaultRow::class, $first);

        // $tuple = $first->getTuple();
        // $this->assertInstanceOf(DefaultTuple::class, $tuple);
    }
}
