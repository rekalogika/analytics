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

namespace Rekalogika\Analytics\PivotTable\Util;

use Rekalogika\Analytics\PivotTable\BranchNode;
use Rekalogika\Analytics\PivotTable\LeafNode;
use Rekalogika\Analytics\PivotTable\Model\NullBranchNode;
use Rekalogika\Analytics\PivotTable\Model\NullLeafNode;
use Rekalogika\Analytics\PivotTable\TreeNode;

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
                if ($child->isLeaf()) {
                    /** @var LeafNode $child */
                    $childNulls[] = NullLeafNode::fromInterface($child);
                } else {
                    /** @var BranchNode $child */
                    $childNulls[] = NullBranchNode::fromInterface($child);
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

                        if ($node->isLeaf()) {
                            /** @var LeafNode $node */
                            $merged[$level][] = NullLeafNode::fromInterface($node);
                        } else {
                            /** @var BranchNode $node */
                            $merged[$level][] = NullBranchNode::fromInterface($node);
                        }
                    }
                }
            }
        }

        return array_values($merged);
    }
}
