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

use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RollUpQueryTest extends KernelTestCase
{
    /**
     * The roll-up SQL must be identical across invocations, so that the
     * database can reuse the query plan.
     */
    public function testSourceToSummaryRollUpSqlIsDeterministic(): void
    {
        $first = $this->getSourceToSummaryRollUpSql();
        $second = $this->getSourceToSummaryRollUpSql();

        $this->assertStringContainsString('REKALOGIKA_BUST(', $first);
        $this->assertSame($first, $second);
    }

    private function getSourceToSummaryRollUpSql(): string
    {
        $factory = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $factory);

        $query = $factory
            ->createSummaryRefresher(OrderSummary::class)
            ->getSqlFactory()
            ->getRollUpSourceToSummaryQuery();

        $sql = '';

        foreach ($query->getQueries() as $decomposedQuery) {
            $sql .= $decomposedQuery->getSql() . ";\n";
        }

        return $sql;
    }
}
