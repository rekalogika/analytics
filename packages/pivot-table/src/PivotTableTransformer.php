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

namespace Rekalogika\PivotTable;

use Rekalogika\PivotTable\Block\Block;
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Table\Table;

final readonly class PivotTableTransformer
{
    private function __construct() {}

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $superfluousLegends
     */
    public static function transformTreeToBlock(
        TreeNode $treeNode,
        array $pivotedNodes = [],
        array $superfluousLegends = [],
    ): Block {
        return Block::new($treeNode, $pivotedNodes, $superfluousLegends);
    }

    public static function transformBlockToTable(Block $block): Table
    {
        return $block->generateTable();
    }

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $superfluousLegends
     */
    public static function transformTreeToTable(
        TreeNode $treeNode,
        array $pivotedNodes = [],
        array $superfluousLegends = [],
    ): Table {

        $block = self::transformTreeToBlock(
            treeNode: $treeNode,
            pivotedNodes: $pivotedNodes,
            superfluousLegends: $superfluousLegends,
        );

        return self::transformBlockToTable($block);
    }
}
