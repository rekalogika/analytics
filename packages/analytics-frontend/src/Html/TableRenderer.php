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
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Frontend\Exception\FrontendWrapperException;
use Rekalogika\Analytics\Frontend\Html\Visitor\TableRendererVisitor;
use Rekalogika\Analytics\Frontend\Internal\PivotTableFactory;
use Rekalogika\Analytics\PivotTable\Adapter\Table\TableAdapter;
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
     * @param CubeCell $cell The root cell that contains the table data.
     * @param list<string> $dimensions The dimensions that will be displayed in
     * the table
     * @param list<string> $measures The measures that will be displayed in the
     * table
     * @param string|null $theme The theme to use for rendering. If null, the
     * default theme will be used.
     * @param bool $throwException If true, the method will throw an exception
     * if an error occurs during rendering. If false, it will return an HTML
     * string with the error message.
     */
    public function renderTable(
        CubeCell $cell,
        array $dimensions,
        array $measures,
        ?string $theme = null,
        bool $throwException = false,
    ): string {
        try {
            return $this->doRenderTable(
                cell: $cell,
                dimensions: $dimensions,
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
        $table = PivotTableFactory::create(
            cube: $cube,
            measures: $measures,
            rows: $rows,
            columns: $columns,
        );

        return $this->getVisitor($theme)->visitTable($table);
    }

    /**
     * @param list<string> $dimensions
     * @param list<string> $measures
     */
    private function doRenderTable(
        CubeCell $cell,
        array $dimensions,
        array $measures,
        ?string $theme = null,
    ): string {
        $table = new TableAdapter(
            cell: $cell,
            dimensions: $dimensions,
            measures: $measures,
        );

        // the transformer does not accept a table without rows
        if ($table->isEmpty()) {
            return $this->twig
                ->load($theme ?? $this->theme)
                ->renderBlock('no_data', [
                    'no_data_message' => new TranslatableMessage('No result returned.'),
                ]);
        }

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
                'exception_title' => new TranslatableMessage('An error occurred'),
            ]);
    }
}
