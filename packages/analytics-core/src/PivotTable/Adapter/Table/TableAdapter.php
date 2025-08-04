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

use Rekalogika\Analytics\Contracts\Result\Table;
use Rekalogika\PivotTable\Contracts\Table as PivotTableTable;

final readonly class TableAdapter implements PivotTableTable
{
    /**
     * @var array<string,mixed>
     */
    private array $legends;

    /**
     * @var list<RowAdapter>
     */
    private array $rows;

    public function __construct(
        private readonly Table $table,
    ) {
        $rows = [];
        $legends = [];

        foreach ($this->table as $row) {
            $rowAdapter = new RowAdapter($row);
            $rows[] = $rowAdapter;
            $legends = array_merge($legends, $rowAdapter->getLegends());
        }

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
