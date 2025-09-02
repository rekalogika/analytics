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

namespace Rekalogika\Analytics\PivotTable\Adapter\Table;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\PivotTable\Model\Table\TableLabel;
use Rekalogika\PivotTable\Contracts\Table\Table as PivotTableTable;

final readonly class TableAdapter implements PivotTableTable
{
    /**
     * @var array<string,mixed>
     */
    private array $legend;

    /**
     * @var iterable<CubeCell>
     */
    private iterable $cells;

    /**
     * @param CubeCell $cell The root cell that contains the table data.
     * @param list<string> $dimensions The dimensions that will be displayed in
     * the table.
     * @param list<string> $measures The measures that will be displayed in the
     * table.
     */
    public function __construct(
        CubeCell $cell,
        array $dimensions,
        private array $measures,
    ) {
        if ($dimensions === []) {
            $this->cells = [$cell];
            $first = $cell;
        } else {
            $this->cells = $cell->drillDown($dimensions);
            $first = $this->cells->first();
        }

        if ($first === null) {
            $this->legend = [];

            return;
        }

        $legend = [];

        foreach ($first->getCoordinates() as $dimension) {
            $legend[$dimension->getName()] = $dimension->getLabel();
        }

        foreach ($measures as $measure) {
            $measureObject = $first
                ->getMeasures()
                ->get($measure)
                ?? throw new InvalidArgumentException(\sprintf('Measure "%s" does not exist in the result.', $measure));

            $legend[$measure] = $measureObject->getLabel();
        }

        $this->legend = $legend;
    }

    #[\Override]
    public function getRows(): iterable
    {
        foreach ($this->cells as $cell) {
            yield $cell->getCoordinates() => new RowAdapter($cell, $this->measures);
        }
    }

    #[\Override]
    public function getLegend(string $key): mixed
    {
        /** @psalm-suppress MixedAssignment */
        $result = $this->legend[$key] ?? null;

        return new TableLabel($result);
    }

    #[\Override]
    public function getSubtotalLabel(string $key): mixed
    {
        return new TranslatableMessage('Total');
    }
}
