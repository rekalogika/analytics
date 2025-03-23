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

use Rekalogika\PivotTable\Contracts\BranchNode;
use Rekalogika\PivotTable\Contracts\LeafNode;
use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Implementation\TreeNode\NullBranchNode;
use Rekalogika\PivotTable\Implementation\TreeNode\NullLeafNode;

final readonly class DistinctNodeListResolver
{
    /**
     * @return list<list<TreeNode>>
     */
    public static function getDistinctNodes(
        TreeNode $treeNode,
    ): array {
        if ($treeNode instanceof BranchNode) {
            $grandChildrenDistincts = [];
            $children = $treeNode->getChildren();

            foreach ($treeNode->getChildren() as $child) {
                if ($child instanceof BranchNode) {
                    $grandChildrenDistincts[] = self::getDistinctNodes($child);
                }
            }

            $childNulls = [];

            foreach ($children as $child) {
                if ($child instanceof BranchNode) {
                    $childNulls[] = NullBranchNode::fromInterface($child);
                } elseif ($child instanceof LeafNode) {
                    $childNulls[] = NullLeafNode::fromInterface($child);
                } else {
                    throw new \LogicException('Unknown node type');
                }
            }

            return [
                $childNulls,
                ...self::mergeDistincts($grandChildrenDistincts),
            ];
        }

        throw new \LogicException('Invalid TreeNode type');
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

                        if ($node instanceof BranchNode) {
                            $merged[$level][] = NullBranchNode::fromInterface($node);
                        } elseif ($node instanceof LeafNode) {
                            $merged[$level][] = NullLeafNode::fromInterface($node);
                        } else {
                            throw new \LogicException('Unknown node type');
                        }
                    }
                }
            }
        }

        return array_values($merged);
    }
}
