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
use Rekalogika\PivotTable\Block\Model\CubeCellDecorator;
use Rekalogika\PivotTable\Contracts\Cube\Cube;
use Rekalogika\PivotTable\Table\Table;

final readonly class PivotTableTransformer
{
    private function __construct() {}

    /**
     * @param list<string> $unpivotedNodes
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     */
    public static function transform(
        Cube $cube,
        array $unpivotedNodes = [],
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Table {
        $cubeCell = CubeCellDecorator::new($cube);

        $block = Block::new(
            cubeCell: $cubeCell,
            unpivotedNodes: $unpivotedNodes,
            pivotedNodes: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );

        return $block->generateTable();
    }
}
