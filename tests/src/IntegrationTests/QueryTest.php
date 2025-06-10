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
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Exception\OverflowException;
use Rekalogika\Analytics\Model\TimeBin\DayOfMonth;
use Rekalogika\Analytics\Model\TimeBin\Hour;
use Rekalogika\Analytics\Model\TimeBin\Month;
use Rekalogika\Analytics\Model\TimeBin\MonthOfYear;
use Rekalogika\Analytics\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
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
            ->groupBy('time.year')
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
            ->groupBy('time.year', 'customerCountry')
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
            ->groupBy('time.year', '@values', 'customerCountry')
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
            ->groupBy('@values', 'time.year', 'customerCountry')
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
            ->groupBy('time.year', 'customerCountry', '@values')
            ->select('count', 'price')
            ->getResult()
            ->getTree();

        $node = $result->traverse('2024', $country->getName(), 'count');
        $this->assertIsInt($node?->getMeasure()?->getValue());
    }

    public function testWhere(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.year')
            ->select('count')
            ->where(Criteria::expr()->eq('time.year', 2024))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
    }

    public function testWhereMonthObject(): void
    {
        $month = Month::createFromDatabaseValue(202410);

        $result = $this->getQuery()
            ->groupBy('time.month')
            ->select('count')
            ->where(Criteria::expr()->eq('time.month', $month))
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
            ->groupBy('time.month')
            ->select('count')
            ->where(Criteria::expr()->in('time.month', $months))
            ->getResult()
            ->getTree();

        $this->assertCount(3, $result);
    }

    public function testWhereMonthDatabaseValue(): void
    {
        $month = 202410;

        $result = $this->getQuery()
            ->groupBy('time.month')
            ->select('count')
            ->where(Criteria::expr()->eq('time.month', $month))
            ->getResult()
            ->getTree();

        $this->assertCount(1, $result);
    }

    public function testWhereWithOr(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.year')
            ->select('count')
            ->where(Criteria::expr()->orX(
                Criteria::expr()->eq('time.year', 2024),
                Criteria::expr()->eq('time.year', 2023),
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
            ->where(Criteria::expr()->eq('time.year', 2024))
            ->getResult()
            ->getTree()
            ->traverse('count')
            ?->getMeasure()?->getValue();

        $this->assertNotNull($withWhere);

        $this->assertLessThan($all, $withWhere);
    }

    public function testOrderByDimension(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.month')
            ->select('count')
            ->orderBy('time.month', Order::Descending)
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
        rsort($sorted);

        $this->assertEquals($months, $sorted);
    }

    public function testOrderByMeasure(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.month')
            ->select('count')
            ->orderBy('count', Order::Descending)
            ->getResult()
            ->getTree();

        $counts = [];

        foreach ($result as $node) {
            /** @psalm-suppress MixedAssignment */
            $counts[] = $node->traverse('count')?->getMeasure()?->getValue();
        }

        // assert that the counts are sorted in descending order
        $sorted = $counts;
        rsort($sorted);

        $this->assertEquals($counts, $sorted);
    }

    public function testQueryResultLimit(): void
    {
        $this->expectException(OverflowException::class);

        $result = $this->getQuery(queryResultLimit: 1)
            ->groupBy('time.hour')
            ->select('count')
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testWhereWithTimeBin(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.hour')
            ->select('count')
            ->where(Criteria::expr()->gte(
                'time.hour',
                Hour::createFromDatabaseValue(2024101010),
            ))
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testWhereWithTimeBinRange(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.hour')
            ->select('count')
            ->where(Criteria::expr()->andX(
                Criteria::expr()->gte(
                    'time.hour',
                    Hour::createFromDatabaseValue(2024101010),
                ),
                Criteria::expr()->lte(
                    'time.hour',
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
            ->groupBy('time.dayOfMonth')
            ->select('count')
            ->where(Criteria::expr()->andX(
                Criteria::expr()->gte('time.dayOfMonth', DayOfMonth::Day10),
                Criteria::expr()->lte('time.dayOfMonth', DayOfMonth::Day12),
            ))
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testWhereWithRecurringTimeBinRangeInteger(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.dayOfMonth')
            ->select('count')
            ->where(Criteria::expr()->andX(
                Criteria::expr()->gte('time.dayOfMonth', 10),
                Criteria::expr()->lte('time.dayOfMonth', 12),
            ))
            ->getResult()
            ->getTree();

        $c = \count($result);
    }

    public function testYearByMonthOfYear(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.year', 'time.monthOfYear')
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
}
