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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryTest extends KernelTestCase
{
    private function getQuery(): SummaryQuery
    {
        return static::getContainer()->get(SummaryManagerRegistry::class)
            ->getManager(OrderSummary::class)
            ->createQuery();
    }

    public function testEmptyQuery(): void
    {
        $result = $this->getQuery()->getResult();
        $this->assertCount(0, $result);
    }

    public function testDimensionWithoutMeasure(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.year')
            ->getResult();

        $this->assertCount(0, $result);
    }

    public function testNoDimensionAndOneMeasure(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->getResult();

        $this->assertCount(1, $result);

        $node = $result->traverse('count');
        $this->assertNotNull($node);
        $this->assertEquals('@values', $node->getKey());
        $this->assertEquals('count', $node->getMeasurePropertyName());
        $this->assertIsInt($node->getValue());
    }

    public function testInvalidDimension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getQuery()
            ->groupBy('invalid')
            ->getResult();
    }

    public function testInvalidMeasure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getQuery()
            ->select('invalid')
            ->getResult();
    }

    public function testTraversal(): void
    {
        $country = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Country::class)
            ->findOneBy(['code' => 'FR']);

        $this->assertNotNull($country);

        $result = $this->getQuery()
            ->groupBy('time.year', 'customerCountry')
            ->select('count', 'price')
            ->getResult();

        $this->assertCount(2, $result);

        // single traverse
        $node1 = $result->traverse('2024', $country, 'count');
        $this->assertNotNull($node1);
        $this->assertIsInt($node1->getValue());

        // multistep traverse
        $node2 = $result
            ->traverse('2024')
            ?->traverse($country)
            ?->traverse('count');

        $this->assertSame($node1, $node2);

        // single traverse with stringable check
        $node3 = $result->traverse('2024', 'France', 'count');
        $this->assertSame($node1, $node3);

        // multistep traverse with stringable check
        $node4 = $result
            ->traverse('2024')
            ?->traverse('France')
            ?->traverse('count');

        $this->assertSame($node1, $node4);
    }

    public function testGroupByValueType(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.year', '@values', 'customerCountry')
            ->select('count', 'price')
            ->getResult();

        $node = $result->traverse('2024', 'count', 'France');
        $this->assertIsInt($node?->getValue());
    }

    public function testGroupByValueTypeFirst(): void
    {
        $result = $this->getQuery()
            ->groupBy('@values', 'time.year', 'customerCountry')
            ->select('count', 'price')
            ->getResult();

        $node = $result->traverse('count', '2024', 'France');
        $this->assertIsInt($node?->getValue());
    }

    public function testGroupByValueTypeLast(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.year', 'customerCountry', '@values')
            ->select('count', 'price')
            ->getResult();

        $node = $result->traverse('2024', 'France', 'count');
        $this->assertIsInt($node?->getValue());
    }
}
