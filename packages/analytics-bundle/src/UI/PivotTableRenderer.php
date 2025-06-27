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

use Rekalogika\Analytics\Bundle\UI\Implementation\PivotTableRendererVisitor;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\PivotTable\Adapter\PivotTableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;
use Twig\Environment;

final readonly class PivotTableRenderer
{
    private PivotTableRendererVisitor $visitor;

    public function __construct(
        Environment $twig,
        string $theme = '@RekalogikaAnalytics/bootstrap_5_renderer.html.twig',
    ) {
        $this->visitor = new PivotTableRendererVisitor($twig, $theme);
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

        return $this->visitor->visitTable($table);
    }
}
