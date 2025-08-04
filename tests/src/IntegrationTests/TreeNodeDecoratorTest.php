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
use Rekalogika\Analytics\PivotTable\Adapter\Table\TableAdapter;
use Rekalogika\Analytics\Tests\App\Entity\CustomerType;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Rekalogika\PivotTable\TableFramework\Manager;
use Rekalogika\PivotTable\Util\TreeNodeDebugger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TreeNodeDecoratorTest extends KernelTestCase
{
    public function testBasic(): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        // @see http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerCountry%22%2C%22customerType%22%2C%22customerGender%22%5D%2C%22columns%22%3A%5B%22%40values%22%5D%2C%22values%22%3A%5B%22price%22%2C%22count%22%5D%2C%22filterExpressions%22%3A%7B%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22customerGender%22%3A%7B%22dimension%22%3A%22customerGender%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        $query = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->select('count', 'price')
            ->groupBy('customerCountry', 'customerType', 'customerGender', '@values');

        $result = $query->getResult();

        $tableAdapter = TableAdapter::adapt($result->getCube());
        $manager = new Manager($tableAdapter);
        $treeNode = $manager->createTree(['customerCountry', 'customerType', 'customerGender', '@values']);
        $node = TreeNodeDecorator::decorate($treeNode);

        $canada = $this->find(
            $node,
            /** * @psalm-suppress MixedMethodCall */
            fn(TreeNodeDecorator $child) => $child->getItem()->getContent()->getName() === 'Canada',
        );

        $this->assertInstanceOf(TreeNodeDecorator::class, $canada);

        $organizational = $this->find(
            $canada,
            /** @psalm-suppress MixedMethodCall */
            fn(TreeNodeDecorator $child) => $child->getItem()->getContent() === CustomerType::Organizational,
        );

        $this->assertInstanceOf(TreeNodeDecorator::class, $organizational);

        // starting testing getParentByLevel
        $treeNodeDecoratorClass = new \ReflectionClass(TreeNodeDecorator::class);

        // test getParentByLevel by one
        $getParentByLevel = $treeNodeDecoratorClass->getMethod('getParentByLevel');
        $output = $getParentByLevel->invoke($organizational, 1);
        $this->assertSame($canada, $output);

        // test getParentByLevel by zero
        $getParentByLevel = $treeNodeDecoratorClass->getMethod('getParentByLevel');
        /** @psalm-suppress MixedAssignment */
        $output = $getParentByLevel->invoke($organizational, 0);
        $this->assertSame($organizational, $output);

        // starting testing getBalancedChildItems
        $method = $treeNodeDecoratorClass->getMethod('getChildrenSeenByParent');

        // test getBalancedChildItems 1 0
        /** @psalm-suppress MixedAssignment */
        $output = $method->invoke($organizational, 1, 0);
        $this->assertIsArray($output);
        $this->assertCount(1, $output);

        // test getBalancedChildItems 1 1
        /** @psalm-suppress MixedAssignment */
        $output = $method->invoke($organizational, 1, 1);
        $this->assertIsArray($output);
        $this->assertGreaterThan(1, $output);

        // test getBalancedChildren
        $children = $organizational->getBalancedChildren(1, 0);
        $this->assertCount(1, $children);

        $children = $organizational->getBalancedChildren(1, 1);
        $this->assertGreaterThan(1, $children);


        // dump(TreeNodeDebugger::debug($parent));
    }

    /**
     * @param callable(TreeNodeDecorator): bool $condition
     */
    private function find(
        TreeNodeDecorator $treeNode,
        callable $condition,
    ): ?TreeNodeDecorator {
        foreach ($treeNode->getChildren() as $child) {
            $found = $condition($child);

            if ($found) {
                return $child;
            }
        }

        return null;
    }
}
