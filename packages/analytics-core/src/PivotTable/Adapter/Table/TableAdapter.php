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

use Rekalogika\Analytics\Contracts\Result\Cube;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\PivotTable\Model\Table\MeasureDimensionLabel;
use Rekalogika\Analytics\PivotTable\Util\TablePropertyMap;
use Rekalogika\PivotTable\Contracts\Table as PivotTableTable;

final readonly class TableAdapter implements PivotTableTable
{
    public static function adapt(Cube $cube): self
    {
        return new self($cube);
    }

    /**
     * @var array<string,mixed>
     */
    private array $legends;

    /**
     * @var list<RowAdapter>
     */
    private array $rows;

    private TablePropertyMap $propertyMap;

    private function __construct(
        private readonly Cube $cube,
    ) {
        $this->propertyMap = new TablePropertyMap();

        $rows = [];
        $legends = [];

        foreach ($this->cube as $row) {
            $rowAdapter = new RowAdapter($row, $this->propertyMap);
            $rows[] = $rowAdapter;
            $legends = array_merge($legends, $rowAdapter->getLegends());
        }

        $legends['@values'] = new MeasureDimensionLabel(new TranslatableMessage('Values'));

        $this->rows = $rows;
        $this->legends = $legends;
    }

    #[\Override]
    public function getRows(): iterable
    {
        return $this->rows;
    }

    #[\Override]
    public function getLegend(string $key): mixed
    {
        return $this->legends[$key] ?? null;
    }
}
