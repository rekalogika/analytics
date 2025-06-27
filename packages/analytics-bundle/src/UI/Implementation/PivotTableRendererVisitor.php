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

namespace Rekalogika\Analytics\Bundle\UI\Implementation;

use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\PivotTable\Adapter\PivotTableAdapter;
use Rekalogika\Analytics\PivotTable\Model\Label;
use Rekalogika\Analytics\PivotTable\Model\Member;
use Rekalogika\Analytics\PivotTable\Model\Property;
use Rekalogika\Analytics\PivotTable\Model\Value;
use Rekalogika\Analytics\PivotTable\TableVisitor;
use Rekalogika\PivotTable\PivotTableTransformer;
use Rekalogika\PivotTable\Table\Cell;
use Rekalogika\PivotTable\Table\Table;
use Rekalogika\PivotTable\Table\TableHeader;
use Rekalogika\PivotTable\Table\TableBody;
use Rekalogika\PivotTable\Table\TableFooter;
use Rekalogika\PivotTable\Table\Row;
use Rekalogika\PivotTable\Table\HeaderCell;
use Rekalogika\PivotTable\Table\DataCell;
use Rekalogika\PivotTable\Table\Element;
use Rekalogika\PivotTable\Table\FooterCell;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * @implements TableVisitor<string>
 * @internal
 */
final readonly class PivotTableRendererVisitor implements TableVisitor
{
    private TemplateWrapper $template;

    public function __construct(
        Environment $twig,
        string $theme = '@RekalogikaAnalytics/bootstrap_5_renderer.html.twig',
    ) {
        $this->template = $twig->load($theme);
    }

    /**
     * @param \Traversable<Element> $element
     * @param string $block
     * @param array $parameters
     * @return string
     */
    private function renderWithChildren(
        \Traversable $element,
        string $block,
        array $parameters = []
    ): string {
        return $this->template->renderBlock($block, [
            'element' => $element,
            'children' => $this->getChildren($element),
            ...$parameters,
        ]);
    }

    private function renderCell(Cell $cell, string $block): string
    {
        $content = $cell->getContent();

        if ($content instanceof Property) {
            // If the content is a Property, we need to render it using its accept method
            $content = $content->accept($this);
        }

        return $this->template->renderBlock('th', [
            'element' => $cell,
            'content' => $content,
        ]);
    }

    /**
     * @param \Traversable<Element> $node
     * @return \Traversable<T>
     */
    private function getChildren(\Traversable $node): \Traversable
    {
        foreach ($node as $child) {
            yield $child->accept($this);
        }
    }

    public function visitTable(Table $table): mixed
    {
        return $this->renderWithChildren($table, 'table');
    }

    public function visitTableHeader(TableHeader $tableHeader): mixed
    {
        return $this->renderWithChildren($tableHeader, 'thead');
    }

    public function visitTableBody(TableBody $tableBody): mixed
    {
        return $this->renderWithChildren($tableBody, 'tbody');
    }

    public function visitTableFooter(TableFooter $tableFooter): mixed
    {
        return $this->renderWithChildren($tableFooter, 'tfoot');
    }

    public function visitRow(Row $tableRow): mixed
    {
        return $this->renderWithChildren($tableRow, 'tr');
    }

    public function visitHeaderCell(HeaderCell $headerCell): mixed
    {
        return $this->renderCell($headerCell, 'th');
    }

    public function visitDataCell(DataCell $dataCell): mixed
    {
        dump($dataCell);
        $result = $this->renderCell($dataCell, 'td');
        dump($result);

        return $result;
    }

    public function visitFooterCell(FooterCell $footerCell): mixed
    {
        return $this->renderCell($footerCell, 'tf');
    }

    public function visitLabel(Label $label): mixed
    {
        return $this->template->renderBlock('label', [
            'label' => $label,
        ]);
    }

    public function visitMember(Member $member): mixed
    {
        return $this->template->renderBlock('member', [
            'member' => $member,
        ]);
    }

    public function visitValue(Value $value): mixed
    {
        return $this->template->renderBlock('value', [
            'value' => $value,
        ]);
    }
}
