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

use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\PivotTable\Model\Property;
use Rekalogika\PivotTable\Table\Cell;
use Rekalogika\PivotTable\Table\DataCell;
use Rekalogika\PivotTable\Table\FooterCell;
use Rekalogika\PivotTable\Table\HeaderCell;
use Twig\Environment;

/**
 * @internal
 */
final readonly class SpreadSheetRendererVisitor extends HtmlRendererVisitor
{
    public function __construct(
        Environment $twig,
        string $theme,
        private Cellifier $cellifier,
    ) {
        parent::__construct($twig, $theme);
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
            /** @psalm-suppress MixedAssignment */
            $content = $content->getContent();
        }

        $cellProperties = $this->cellifier->toCell($content);

        return $this->getTemplate()->renderBlock($block, [
            'element' => $cell,
            'content' => $cellProperties->getContent(),
            'cell_properties' => $cellProperties,
            ...$parameters,
        ]);
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
