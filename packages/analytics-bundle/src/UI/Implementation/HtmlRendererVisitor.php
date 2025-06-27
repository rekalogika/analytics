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

use Rekalogika\Analytics\PivotTable\Model\Label;
use Rekalogika\Analytics\PivotTable\Model\Member;
use Rekalogika\Analytics\PivotTable\Model\Property;
use Rekalogika\Analytics\PivotTable\Model\Value;
use Rekalogika\Analytics\PivotTable\TableVisitor;
use Rekalogika\PivotTable\Table\Cell;
use Rekalogika\PivotTable\Table\DataCell;
use Rekalogika\PivotTable\Table\Element;
use Rekalogika\PivotTable\Table\FooterCell;
use Rekalogika\PivotTable\Table\HeaderCell;
use Rekalogika\PivotTable\Table\Row;
use Rekalogika\PivotTable\Table\Table;
use Rekalogika\PivotTable\Table\TableBody;
use Rekalogika\PivotTable\Table\TableFooter;
use Rekalogika\PivotTable\Table\TableHeader;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * @implements TableVisitor<string>
 * @internal
 */
readonly class HtmlRendererVisitor implements TableVisitor
{
    private TemplateWrapper $template;

    public function __construct(
        Environment $twig,
        string $theme,
    ) {
        $this->template = $twig->load($theme);
    }

    protected function getTemplate(): TemplateWrapper
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

        if ($content instanceof Property) {
            $content = $content->accept($this);
        }

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
    protected function renderChildren(\Traversable $node): \Traversable
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

    #[\Override]
    public function visitLabel(Label $label): mixed
    {
        return $this->getTemplate()->renderBlock('label', [
            'label' => $label,
        ]);
    }

    #[\Override]
    public function visitMember(Member $member): mixed
    {
        return $this->getTemplate()->renderBlock('member', [
            'member' => $member,
        ]);
    }

    #[\Override]
    public function visitValue(Value $value): mixed
    {
        return $this->getTemplate()->renderBlock('value', [
            'value' => $value,
        ]);
    }
}
