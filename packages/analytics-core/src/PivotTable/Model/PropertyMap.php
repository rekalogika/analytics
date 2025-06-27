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

namespace Rekalogika\Analytics\PivotTable\Model;

use Rekalogika\Analytics\Contracts\Result\TreeNode;

/**
 * Identity map for objects that represent properties of a TreeNode. It needs
 * an identity map because pivot-table depends on identity comparison.
 */
final class PropertyMap
{
    /**
     * @var \WeakMap<TreeNode,Label>
     */
    private \WeakMap $labelMap;

    /**
     * @var \WeakMap<TreeNode,Member>
     */
    private \WeakMap $memberMap;

    /**
     * @var \WeakMap<TreeNode,Value>
     */
    private \WeakMap $valueMap;

    public function __construct()
    {
        $this->labelMap = new \WeakMap();
        $this->memberMap = new \WeakMap();
        $this->valueMap = new \WeakMap();
    }

    public function getLabel(TreeNode $treeNode): Label
    {
        return $this->labelMap[$treeNode] ??= new Label($treeNode);
    }

    public function getMember(TreeNode $treeNode): Member
    {
        return $this->memberMap[$treeNode] ??= new Member($treeNode);
    }

    public function getValue(TreeNode $treeNode): Value
    {
        return $this->valueMap[$treeNode] ??= new Value($treeNode);
    }
}
