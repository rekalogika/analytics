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

use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\PivotTable\Adapter\Tree\PivotTableTreeNodeAdapter;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Rekalogika\PivotTable\Util\TreeNodeDebugger;

final class TreeNodeDecoratorTest extends KernelTestCase
{
    public function testBasic(): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        $query = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->select('count', 'price')
            ->groupBy('customerType', 'customerGender', '@values');

        $result = $query->getResult();

        $treeNode = PivotTableTreeNodeAdapter::adapt($result->getTree());
        $decorated = TreeNodeDecorator::decorate($treeNode);

        foreach ($decorated->getChildren() as $child) {
            dump(TreeNodeDebugger::debug($child));
        }
    }

    /**
     * @param callable(): bool $condition
     */
    private function find(
        TreeNodeDecorator $treeNode,
        callable $condition
    ): ?TreeNodeDecorator {
        foreach ($treeNode->getChildren() as $child) {
            $found = $this->find($child, $condition);
            
            if ($found !== null) {
                return $child;
            }
        }

        return null;
    }
}
