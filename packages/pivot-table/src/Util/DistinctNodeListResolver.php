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

namespace Rekalogika\PivotTable\Util;

use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Rekalogika\PivotTable\Decorator\TreeNodeDecoratorRepository;
use Rekalogika\PivotTable\Implementation\TreeNode\NullTreeNode;

final readonly class DistinctNodeListResolver
{
    /**
     * @return list<list<TreeNodeDecorator>>
     */
    public static function getDistinctNodes(
        TreeNodeDecorator $node,
        TreeNodeDecoratorRepository $repository,
    ): array {
        if ($node->isLeaf()) {
            throw new \LogicException('Invalid TreeNodeDecorator type');
        }

        $grandChildrenDistincts = [];
        $children = $node->getChildren();

        foreach ($node->getChildren() as $child) {
            if (!$child->isLeaf()) {
                $grandChildrenDistincts[] = self::getDistinctNodes($child, $repository);
            }
        }

        $childNulls = [];

        foreach ($children as $child) {
            $nullNode = NullTreeNode::fromInterface($child);
            $childNulls[] = $repository->decorate($nullNode, $node);
        }

        return [
            $childNulls,
            ...self::mergeDistincts($grandChildrenDistincts, $repository),
        ];
    }

    /**
     * @param list<list<list<TreeNodeDecorator>>> $distincts
     * @return list<list<TreeNodeDecorator>>
     */
    private static function mergeDistincts(
        array $distincts,
        TreeNodeDecoratorRepository $repository,
    ): array {
        $values = [];
        $merged = [];

        foreach ($distincts as $distinct) {
            foreach ($distinct as $level => $nodes) {
                foreach ($nodes as $node) {
                    if (!isset($values[$level])) {
                        $values[$level] = [];
                        $merged[$level] = [];
                    }


                    if (!\in_array($node->getItem(), $values[$level], true)) {
                        /** @psalm-suppress MixedAssignment */
                        $values[$level][] = $node->getItem();

                        $nullNode = NullTreeNode::fromInterface($node);
                        $decorated = $repository->decorate($nullNode, null);

                        $merged[$level][] = $decorated;
                    }
                }
            }
        }

        return array_values($merged);
    }
}
