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

namespace Rekalogika\Analytics\Frontend\Html\Visitor;

use Rekalogika\PivotTable\HtmlTable\Cell;
use Rekalogika\PivotTable\HtmlTable\DataCell;
use Rekalogika\PivotTable\HtmlTable\Element;
use Rekalogika\PivotTable\HtmlTable\FooterCell;
use Rekalogika\PivotTable\HtmlTable\HeaderCell;
use Rekalogika\PivotTable\HtmlTable\Row;
use Rekalogika\PivotTable\HtmlTable\Table;
use Rekalogika\PivotTable\HtmlTable\TableBody;
use Rekalogika\PivotTable\HtmlTable\TableFooter;
use Rekalogika\PivotTable\HtmlTable\TableHeader;
use Rekalogika\PivotTable\HtmlTable\TableVisitor;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * @implements TableVisitor<string>
 */
final readonly class TableRendererVisitor implements TableVisitor
{
    private TemplateWrapper $template;

    public function __construct(
        Environment $twig,
        string $theme,
    ) {
        $this->template = $twig->load($theme);
    }

    private function getTemplate(): TemplateWrapper
    {
        return $this->template;
    }

    /**
     * @param \Traversable<Element> $element
     * @param string $block
     * @param array<string,mixed> $parameters
     * @return string
     */
    private function renderElementWithChildren(
        \Traversable $element,
        string $block,
        array $parameters = [],
    ): string {
        return $this->getTemplate()->renderBlock($block, [
            'element' => $element,
            'children' => $this->renderChildren($element),
            ...$parameters,
        ]);
    }

    /**
     * @param array<string,mixed> $parameters
     */
    private function renderCell(
        Cell $cell,
        string $block,
        array $parameters = [],
    ): string {
        /** @psalm-suppress MixedAssignment */
        $content = $cell->getContent();

        return $this->getTemplate()->renderBlock($block, [
            'element' => $cell,
            'content' => $content,
            ...$parameters,
        ]);
    }

    /**
     * @param \Traversable<Element> $node
     * @return \Traversable<string>
     */
    private function renderChildren(\Traversable $node): \Traversable
    {
        foreach ($node as $child) {
            yield $child->accept($this);
        }
    }

    #[\Override]
    public function visitTable(Table $table): mixed
    {
        return $this->renderElementWithChildren($table, 'table');
    }

    #[\Override]
    public function visitTableHeader(TableHeader $tableHeader): mixed
    {
        return $this->renderElementWithChildren($tableHeader, 'thead');
    }

    #[\Override]
    public function visitTableBody(TableBody $tableBody): mixed
    {
        return $this->renderElementWithChildren($tableBody, 'tbody');
    }

    #[\Override]
    public function visitTableFooter(TableFooter $tableFooter): mixed
    {
        return $this->renderElementWithChildren($tableFooter, 'tfoot');
    }

    #[\Override]
    public function visitRow(Row $tableRow): mixed
    {
        return $this->renderElementWithChildren($tableRow, 'tr');
    }

    #[\Override]
    public function visitHeaderCell(HeaderCell $headerCell): mixed
    {
        return $this->renderCell($headerCell, 'th');
    }

    #[\Override]
    public function visitDataCell(DataCell $dataCell): mixed
    {
        return $this->renderCell($dataCell, 'td');
    }

    #[\Override]
    public function visitFooterCell(FooterCell $footerCell): mixed
    {
        return $this->renderCell($footerCell, 'tf');
    }
}
