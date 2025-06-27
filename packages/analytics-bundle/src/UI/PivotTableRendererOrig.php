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

namespace Rekalogika\Analytics\Bundle\UI;

use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\PivotTable\Adapter\PivotTableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;
use Twig\Environment;

final readonly class PivotTableRendererOrig
{
    public function __construct(
        private Environment $twig,
        private string $theme = '@RekalogikaAnalytics/bootstrap_5_renderer.html.twig',
    ) {}

    /**
     * @param array<string,mixed> $parameters
     */
    private function renderBlock(string $block, array $parameters): string
    {
        return $this->twig
            ->load($this->theme)
            ->renderBlock($block, $parameters);
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    public function createPivotTable(
        Result $result,
        array $pivotedDimensions = [],
    ): string {
        $treeResult = $result->getTree();
        $pivotTable = PivotTableAdapter::adapt($treeResult);

        $table = PivotTableTransformer::transformTreeNodeToPivotTable(
            treeNode: $pivotTable,
            pivotedNodes: $pivotedDimensions,
            superfluousLegends: ['@values'],
        );

        return $this->renderBlock('table', [
            'table' => $table,
        ]);
    }
}
