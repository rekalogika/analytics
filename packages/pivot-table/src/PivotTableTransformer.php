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
use Rekalogika\PivotTable\Contracts\BranchNode;
use Rekalogika\PivotTable\Table\Table;

final readonly class PivotTableTransformer
{
    private function __construct() {}

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $superfluousLegends
     */
    public static function transformTreeNodeToBlock(
        BranchNode $treeNode,
        array $pivotedNodes = [],
        array $superfluousLegends = [],
    ): Block {
        return Block::new($treeNode, $pivotedNodes, $superfluousLegends);
    }

    public static function transformBlockToPivotTable(Block $block): Table
    {
        return $block->generateTable();
    }

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $superfluousLegends
     */
    public static function transformTreeNodeToPivotTable(
        BranchNode $treeNode,
        array $pivotedNodes = [],
        array $superfluousLegends = [],
    ): Table {
        
        $block = self::transformTreeNodeToBlock(
            treeNode: $treeNode,
            pivotedNodes: $pivotedNodes,
            superfluousLegends: $superfluousLegends,
        );

        return self::transformBlockToPivotTable($block);
    }
}
