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

namespace Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper;

use Rekalogika\Analytics\Contracts\TreeNode;

final class NodeWrapperFactory
{
    /**
     * @var \WeakMap<TreeNode,NodeLabel>
     */
    private \WeakMap $labelCache;

    /**
     * @var \WeakMap<TreeNode,NodeMember>
     */
    private \WeakMap $memberCache;


    /**
     * @var \WeakMap<TreeNode,NodeValue>
     */
    private \WeakMap $valueCache;

    public function __construct()
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->labelCache = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->memberCache = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->valueCache = new \WeakMap();
    }

    public function getLabel(TreeNode $treeNode): NodeLabel
    {
        /**
         * @var NodeLabel
         * @psalm-suppress PossiblyNullArgument
         */
        return $this->labelCache[$treeNode] ??= new NodeLabel($treeNode);
    }

    public function getMember(TreeNode $treeNode): NodeMember
    {
        /**
         * @var NodeMember
         * @psalm-suppress PossiblyNullArgument
         */
        return $this->memberCache[$treeNode] ??= new NodeMember($treeNode);
    }

    public function getValue(TreeNode $treeNode): NodeValue
    {
        /**
         * @var NodeValue
         * @psalm-suppress PossiblyNullArgument
         */
        return $this->valueCache[$treeNode] ??= new NodeValue($treeNode);
    }
}
