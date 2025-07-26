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

namespace Rekalogika\PivotTable\Block;

use Rekalogika\PivotTable\Block\Util\Subtotals;
use Rekalogika\PivotTable\Contracts\Tree\BranchNode;
use Rekalogika\PivotTable\Contracts\Tree\LeafNode;
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;
use Rekalogika\PivotTable\Implementation\Table\DefaultTable;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableBody;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableFooter;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableHeader;
use Rekalogika\PivotTable\Util\DistinctNodeListResolver;

abstract class Block implements \Stringable
{
    protected function __construct(
        private readonly int $level,
        private readonly BlockContext $context,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%s(level: %d)',
            static::class,
            $this->level,
        );
    }

    private static function createByType(
        TreeNode $treeNode,
        int $level,
        BlockContext $context,
    ): Block {
        if ($treeNode instanceof BranchNode) {
            if ($context->isPivoted($treeNode)) {
                return new PivotBlock($treeNode, $level, $context);
            } else {
                return new NormalBlock($treeNode, $level, $context);
            }
        }

        if ($treeNode instanceof LeafNode) {
            if ($context->isPivoted($treeNode)) {
                return new PivotLeafBlock($treeNode, $level, $context);
            } elseif (\count($context->getDistinctNodesOfLevel($level - 1)) === 1) {
                return new SingleNodeLeafBlock($treeNode, $level, $context);
            } else {
                return new NormalLeafBlock($treeNode, $level, $context);
            }
        }

        throw new \LogicException('Unknown node type');
    }

    final protected function getLevel(): int
    {
        return $this->level;
    }

    final protected function createBlock(TreeNode $treeNode, int $level): Block
    {
        return self::createByType($treeNode, $level, $this->getContext());
    }

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $superfluousLegends
     */
    final public static function new(
        BranchNode $treeNode,
        array $pivotedNodes = [],
        array $superfluousLegends = [],
    ): Block {
        $distinct = DistinctNodeListResolver::getDistinctNodes($treeNode);

        $context = new BlockContext(
            distinct: $distinct,
            pivotedDimensions: $pivotedNodes,
            superfluousLegends: $superfluousLegends,
        );

        return new RootBlock($treeNode, $context);
    }

    final public static function newWithoutRoot(BranchNode $treeNode, int $level): Block
    {
        $distinct = DistinctNodeListResolver::getDistinctNodes($treeNode);

        return self::createByType($treeNode, $level, new BlockContext($distinct));
    }

    final protected function getContext(): BlockContext
    {
        return $this->context;
    }

    /**
     * @param non-empty-list<BranchNode> $branchNodes
     * @return non-empty-list<BranchNode>
     */
    final protected function balanceBranchNodes(array $branchNodes, int $level): array
    {
        $distinctBranchNodes = $this->getContext()->getDistinctNodesOfLevel($level);

        $result = [];

        foreach ($distinctBranchNodes as $distinctBranchNode) {
            $found = false;

            foreach ($branchNodes as $branchNode) {
                // @todo fix identity comparison
                if ($branchNode->getItem() === $distinctBranchNode->getItem()) {
                    $result[] = $branchNode;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $result[] = $distinctBranchNode;
            }
        }

        /** @var non-empty-list<BranchNode> $result */
        return $result;
    }

    abstract public function getHeaderRows(): DefaultRows;

    abstract public function getDataRows(): DefaultRows;

    abstract public function getDataPaddingRows(): DefaultRows;

    abstract public function getSubtotalHeaderRows(
        Subtotals $subtotals,
    ): DefaultRows;

    abstract public function getSubtotalDataRows(
        Subtotals $subtotals,
    ): DefaultRows;

    final public function generateTable(): DefaultTable
    {
        return new DefaultTable(
            [
                new DefaultTableHeader($this->getHeaderRows(), $this),
                new DefaultTableBody($this->getDataRows(), $this),
                new DefaultTableFooter(new DefaultRows([], $this), $this),
            ],
            generatingBlock: $this,
        );
    }
}
