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

use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\SummaryManagerRegistry;
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
}
