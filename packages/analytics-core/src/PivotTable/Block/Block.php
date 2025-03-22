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

namespace Rekalogika\Analytics\PivotTable\Block;

use Rekalogika\Analytics\PivotTable\BranchNode;
use Rekalogika\Analytics\PivotTable\LeafNode;
use Rekalogika\Analytics\PivotTable\Table\Rows;
use Rekalogika\Analytics\PivotTable\Table\Table;
use Rekalogika\Analytics\PivotTable\TreeNode;
use Rekalogika\Analytics\PivotTable\Util\DistinctNodeListResolver;

abstract class Block
{
    private ?Rows $headerRowsCache = null;

    private ?Rows $dataRowsCache = null;

    protected function __construct(
        private readonly int $level,
        private readonly BlockContext $context,
    ) {}

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
        TreeNode $treeNode,
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

    final public function createGroupBlock(BranchNode $parentNode, int $level): Block
    {
        /** @var \Traversable<array-key,TreeNode> */
        $children = $parentNode->getChildren();
        $children = iterator_to_array($children);

        $firstChild = $children[0] ?? null;

        if ($firstChild === null) {
            $firstChild = $this->getContext()->getDistinctNodesOfLevel($level)[0] ?? null;
        }

        if ($firstChild === null) {
            return new EmptyBlock($parentNode, $level, $this->getContext());
        }

        if ($this->context->isPivoted($firstChild)) {
            return new HorizontalBlockGroup($parentNode, $level, $this->getContext());
        } else {
            return new VerticalBlockGroup($parentNode, $level, $this->getContext());
        }
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

    final protected function getHeaderRows(): Rows
    {
        return $this->headerRowsCache ??= $this->createHeaderRows();
    }

    final protected function getDataRows(): Rows
    {
        return $this->dataRowsCache ??= $this->createDataRows();
    }

    abstract protected function createHeaderRows(): Rows;

    abstract protected function createDataRows(): Rows;

    final public function generateTable(): Table
    {
        return new Table(
            header: $this->getHeaderRows(),
            body: $this->getDataRows(),
            footer: new Rows([]),
        );
    }
}
