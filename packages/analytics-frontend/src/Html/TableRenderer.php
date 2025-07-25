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
     * Render a pivot table or a regular table based on the result.
     *
     * @param list<string> $pivotedDimensions The dimensions that will be
     * pivoted in the table. Specify the special value '@values' to pivot the
     * measure dimension.
     * @param string|null $theme The theme to use for rendering. If null, the
     * default theme will be used.
     * @param bool $throwException If true, the method will throw an exception
     * if an error occurs during rendering. If false, it will return an HTML
     * string with the error message.
     */
    public function render(
        Result $result,
        array $pivotedDimensions = ['@values'],
        ?string $theme = null,
        bool $throwException = false,
    ): string {
        try {
            try {
                return $this->doRenderPivotTable(
                    result: $result,
                    pivotedDimensions: $pivotedDimensions,
                    theme: $theme,
                );
            } catch (HierarchicalOrderingRequired) {
                return $this->doRenderTable(
                    result: $result,
                    theme: $theme,
                );
            }
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
     * Render a pivot table with the specified dimensions.
     *
     * @param list<string> $pivotedDimensions
     * @param string|null $theme The theme to use for rendering. If null, the
     * default theme will be used.
     * @param bool $throwException If true, the method will throw an exception
     * if an error occurs during rendering. If false, it will return an HTML
     * string with the error message.
     *
     * @throws HierarchicalOrderingRequired Thrown if the result does not have a
     * hierarchical ordering because a pivot table requires hierarchical
     * ordering.
     */
    public function renderPivotTable(
        Result $result,
        array $pivotedDimensions = ['@values'],
        ?string $theme = null,
        bool $throwException = false,
    ): string {
        try {
            return $this->doRenderPivotTable(
                result: $result,
                pivotedDimensions: $pivotedDimensions,
                theme: $theme,
            );
        } catch (HierarchicalOrderingRequired $e) {
            throw $e;
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
     * @param string|null $theme The theme to use for rendering. If null, the
     * default theme will be used.
     * @param bool $throwException If true, the method will throw an exception
     * if an error occurs during rendering. If false, it will return an HTML
     * string with the error message.
     */
    public function renderTable(
        Result $result,
        ?string $theme = null,
        bool $throwException = false,
    ): string {
        try {
            return $this->doRenderTable(
                result: $result,
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
