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
use Rekalogika\Analytics\Frontend\Exception\FrontendWrapperException;
use Rekalogika\Analytics\Frontend\Html\Visitor\TableRendererVisitor;
use Rekalogika\Analytics\PivotTable\Adapter\ResultSet\TableAdapter;
use Rekalogika\Analytics\PivotTable\Adapter\Tree\PivotTableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;
use Rekalogika\PivotTable\Util\ResultSetToTableTransformer;
use Twig\Environment;

final readonly class TableRenderer
{
    public function __construct(
        private Environment $twig,
        private string $theme = '@RekalogikaAnalyticsFrontend/renderer.html.twig',
    ) {}

    private function getVisitor(?string $theme): TableRendererVisitor
    {
        return new TableRendererVisitor(
            twig: $this->twig,
            theme: $theme ?? $this->theme,
        );
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    public function render(
        Result $result,
        array $pivotedDimensions = ['@values'],
    ): string {
        try {
            try {
                return $this->doRenderPivotTable($result, $pivotedDimensions);
            } catch (HierarchicalOrderingRequired) {
                return $this->doRenderTable($result);
            }
        } catch (\Throwable $e) {
            throw FrontendWrapperException::selectiveWrap($e);
        }
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    public function renderPivotTable(
        Result $result,
        array $pivotedDimensions = ['@values'],
        ?string $theme = null,
    ): string {
        try {
            return $this->doRenderPivotTable(
                result: $result,
                pivotedDimensions: $pivotedDimensions,
                theme: $theme,
            );
        } catch (\Throwable $e) {
            throw FrontendWrapperException::selectiveWrap($e);
        }
    }

    public function renderTable(
        Result $result,
        ?string $theme = null,
    ): string {
        try {
            return $this->doRenderTable(
                result: $result,
                theme: $theme,
            );
        } catch (\Throwable $e) {
            throw FrontendWrapperException::selectiveWrap($e);
        }
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    private function doRenderPivotTable(
        Result $result,
        array $pivotedDimensions = ['@values'],
        ?string $theme = null,
    ): string {
        $treeResult = $result->getTree();
        $pivotTable = PivotTableAdapter::adapt($treeResult);

        $table = PivotTableTransformer::transformTreeToTable(
            treeNode: $pivotTable,
            pivotedNodes: $pivotedDimensions,
            superfluousLegends: ['@values'],
        );

        return $this->getVisitor($theme)->visitTable($table);
    }

    private function doRenderTable(
        Result $result,
        ?string $theme = null,
    ): string {
        $table = new TableAdapter($result->getTable());
        $table = ResultSetToTableTransformer::transform($table);

        return $this->getVisitor($theme)->visitTable($table);
    }
}
