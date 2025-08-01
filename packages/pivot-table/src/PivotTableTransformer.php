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
     * @param list<string> $skipLegends
     */
    public static function transformTreeToBlock(
        TreeNode $node,
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
    ): Block {
        return Block::new(
            node: $node,
            pivotedNodes: $pivotedNodes,
            skipLegends: $skipLegends,
        );
    }

    public static function transformBlockToTable(Block $block): Table
    {
        return $block->generateTable();
    }

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     */
    public static function transformTreeToTable(
        TreeNode $node,
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
    ): Table {

        $block = self::transformTreeToBlock(
            node: $node,
            pivotedNodes: $pivotedNodes,
            skipLegends: $skipLegends,
        );

        return self::transformBlockToTable($block);
    }
}
