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

namespace Rekalogika\Analytics\Frontend\Html;

use Rekalogika\Analytics\Common\Exception\HierarchicalOrderingRequired;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Frontend\Html\Internal\HtmlRendererVisitor;
use Rekalogika\Analytics\PivotTable\Adapter\ResultSet\TableAdapter;
use Rekalogika\Analytics\PivotTable\Adapter\Tree\PivotTableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;
use Rekalogika\PivotTable\Util\ResultSetToTableTransformer;
use Twig\Environment;

final readonly class HtmlRenderer
{
    private HtmlRendererVisitor $visitor;

    public function __construct(
        Environment $twig,
        string $theme = '@RekalogikaAnalyticsFrontend/bootstrap_5_renderer.html.twig',
    ) {
        $this->visitor = new HtmlRendererVisitor($twig, $theme);
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    public function render(
        Result $result,
        OutputType $outputType = OutputType::Auto,
        array $pivotedDimensions = [],
    ): string {
        return match ($outputType) {
            OutputType::Auto => $this->renderAuto($result, $pivotedDimensions),
            OutputType::PivotTable => $this->renderPivotTable($result, $pivotedDimensions),
            OutputType::Table => $this->renderTable($result),
        };
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    private function renderAuto(
        Result $result,
        array $pivotedDimensions,
    ): string {
        try {
            return $this->renderPivotTable($result, $pivotedDimensions);
        } catch (HierarchicalOrderingRequired) {
            return $this->renderTable($result);
        }
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    private function renderPivotTable(
        Result $result,
        array $pivotedDimensions,
    ): string {
        $treeResult = $result->getTree();
        $pivotTable = PivotTableAdapter::adapt($treeResult);

        $table = PivotTableTransformer::transformTreeToTable(
            treeNode: $pivotTable,
            pivotedNodes: $pivotedDimensions,
            superfluousLegends: ['@values'],
        );

        return $this->visitor->visitTable($table);
    }

    private function renderTable(Result $result): string
    {
        $table = new TableAdapter($result->getTable());
        $table = ResultSetToTableTransformer::transform($table);

        return $this->visitor->visitTable($table);
    }
}
