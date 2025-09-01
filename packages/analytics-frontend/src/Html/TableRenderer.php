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

use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\Table;
use Rekalogika\Analytics\Frontend\Exception\FrontendWrapperException;
use Rekalogika\Analytics\Frontend\Html\Visitor\TableRendererVisitor;
use Rekalogika\Analytics\PivotTable\Adapter\Cube\CubeAdapter;
use Rekalogika\Analytics\PivotTable\Adapter\Table\TableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;
use Rekalogika\PivotTable\Util\TableToHtmlTableTransformer;
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
     * Render a pivot table with the specified dimensions.
     *
     * @param list<string> $measures The measures that will be displayed in the
     * table.
     * @param list<string> $rows The dimensions that will be used as rows in the
     * table.
     * @param list<string> $columns The dimensions that will be pivoted in the
     * table.
     * @param string|null $theme The theme to use for rendering. If null, the
     * default theme will be used.
     * @param bool $throwException If true, the method will throw an exception
     * if an error occurs during rendering. If false, it will return an HTML
     * string with the error message.
     */
    public function renderPivotTable(
        CubeCell $cube,
        array $measures,
        array $rows,
        array $columns = ['@values'],
        ?string $theme = null,
        bool $throwException = false,
    ): string {
        try {
            return $this->doRenderPivotTable(
                cube: $cube,
                rows: $rows,
                columns: $columns,
                measures: $measures,
                theme: $theme,
            );
        } catch (\Throwable $e) {
            $e = FrontendWrapperException::selectiveWrap($e);

            if ($throwException) {
                throw $e;
            }

            return $this->doRenderException(
                exception: $e,
                theme: $theme,
            );
        }
    }

    /**
     * Render a regular table from the result.
     *
     * @param list<string> $measures The measures that will be displayed in the
     * table
     * @param string|null $theme The theme to use for rendering. If null, the
     * default theme will be used.
     * @param bool $throwException If true, the method will throw an exception
     * if an error occurs during rendering. If false, it will return an HTML
     * string with the error message.
     */
    public function renderTable(
        Table $table,
        array $measures,
        ?string $theme = null,
        bool $throwException = false,
    ): string {
        try {
            return $this->doRenderTable(
                table: $table,
                measures: $measures,
                theme: $theme,
            );
        } catch (\Throwable $e) {
            $e = FrontendWrapperException::selectiveWrap($e);

            if ($throwException) {
                throw $e;
            }

            return $this->doRenderException(
                exception: $e,
                theme: $theme,
            );
        }
    }

    /**
     * Filter out the special '@values' dimension from the list of dimensions.
     *
     * @param list<string> $dimensions The list of dimensions to filter.
     * @return list<string> The filtered list of dimensions without '@values'.
     */
    private function filterOutValues(array $dimensions): array
    {
        return array_values(array_filter($dimensions, static fn($dim) => $dim !== '@values'));
    }

    /**
     * @param list<string> $rows
     * @param list<string> $columns
     * @param list<string> $measures
     */
    private function doRenderPivotTable(
        CubeCell $cube,
        array $measures,
        array $rows,
        array $columns,
        ?string $theme = null,
    ): string {
        $cubeAdapter = CubeAdapter::adapt($cube);

        if ($measures === []) {
            $rows = $this->filterOutValues($rows);
            $columns = $this->filterOutValues($columns);
        }

        $dimensions = array_merge($rows, $columns);
        // convert $dimensions to array<string,true>
        $dimensions = array_combine($dimensions, array_fill(0, \count($dimensions), true));

        $table = PivotTableTransformer::transform(
            cube: $cubeAdapter,
            rows: $rows,
            columns: $columns,
            measures: $measures,
            skipLegends: ['@values'],
            subtotals: $dimensions,
        );

        return $this->getVisitor($theme)->visitTable($table);
    }

    /**
     * @param list<string> $measures
     */
    private function doRenderTable(
        Table $table,
        array $measures,
        ?string $theme = null,
    ): string {
        $table = new TableAdapter($table, $measures);
        $table = TableToHtmlTableTransformer::transform($table);

        return $this->getVisitor($theme)->visitTable($table);
    }

    private function doRenderException(
        \Throwable $exception,
        ?string $theme = null,
    ): string {
        $exception = FrontendWrapperException::wrap($exception);

        return $this->twig
            ->load($theme ?? $this->theme)
            ->renderBlock('exception', [
                'exception' => $exception,
            ]);
    }
}
