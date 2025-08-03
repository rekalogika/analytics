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

use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Implementation\TreeNode\NullTreeNode;

final readonly class DistinctNodeListResolver
{
    /**
     * @return list<list<TreeNode>>
     */
    public static function getDistinctNodes(TreeNode $node): array
    {
        if ($node->isLeaf()) {
            throw new \LogicException('Invalid TreeNode type');
        }

        $grandChildrenDistincts = [];
        $children = $node->getChildren();

        foreach ($node->getChildren() as $child) {
            if (!$child->isLeaf()) {
                $grandChildrenDistincts[] = self::getDistinctNodes($child);
            }
        }

        $childNulls = [];

        foreach ($children as $child) {
            $childNulls[] = NullTreeNode::fromInterface($child);
        }

        return [
            $childNulls,
            ...self::mergeDistincts($grandChildrenDistincts),
        ];
    }

    /**
     * @param list<list<list<TreeNode>>> $distincts
     * @return list<list<TreeNode>>
     */
    private static function mergeDistincts(array $distincts): array
    {
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
                        $merged[$level][] = NullTreeNode::fromInterface($node);
                    }
                }
            }
        }

        return array_values($merged);
    }
}
