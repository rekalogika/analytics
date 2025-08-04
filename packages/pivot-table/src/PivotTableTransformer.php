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
use Rekalogika\PivotTable\Contracts\Table as ContractsTable;
use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Table\Table;
use Rekalogika\PivotTable\TableFramework\Manager;

final readonly class PivotTableTransformer
{
    private function __construct() {}

    /**
     * @param list<string> $nodes
     */
    public static function transformTableToTree(
        ContractsTable $table,
        array $nodes = [],
    ): TreeNode {
        return (new Manager($table))->createTree($nodes);
    }

    /**
     * @param list<string> $unpivotedNodes
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     */
    public static function transformTableToBlock(
        ContractsTable $table,
        array $unpivotedNodes = [],
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Block {
        $nodes = [
            ...$unpivotedNodes,
            ...$pivotedNodes,
        ];

        $treeNode = self::transformTableToTree(
            table: $table,
            nodes: $nodes,
        );

        return self::transformTreeToBlock(
            node: $treeNode,
            pivotedNodes: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );
    }

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     */
    public static function transformTreeToBlock(
        TreeNode $node,
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Block {
        return Block::new(
            node: $node,
            pivotedNodes: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );
    }

    public static function transformBlockToTable(Block $block): Table
    {
        return $block->generateTable();
    }

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     */
    public static function transformTreeToTable(
        TreeNode $node,
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Table {
        $block = self::transformTreeToBlock(
            node: $node,
            pivotedNodes: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );

        return self::transformBlockToTable($block);
    }

    /**
     * @param list<string> $unpivotedNodes
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     */
    public static function transformTableToTable(
        ContractsTable $table,
        array $unpivotedNodes = [],
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Table {
        $block = self::transformTableToBlock(
            table: $table,
            unpivotedNodes: $unpivotedNodes,
            pivotedNodes: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );

        return self::transformBlockToTable($block);
    }
}
