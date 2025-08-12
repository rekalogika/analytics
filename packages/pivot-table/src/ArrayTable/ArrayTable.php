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

namespace Rekalogika\PivotTable\ArrayTable;

use Rekalogika\PivotTable\Contracts\Table\Table;

final class ArrayTable implements Table
{
    /**
     * @param iterable<ArrayRow> $rows
     * @param array<string,mixed> $legends
     * @param array<string,mixed>|string $subtotalLegend
     */
    public function __construct(
        private readonly iterable $rows,
        private readonly array $legends,
        private readonly array|string $subtotalLegend = 'Total',
    ) {}

    /**
     * @return iterable<ArrayRow>
     */
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

    #[\Override]
    public function getSubtotalLegend(string $key): mixed
    {
        if (\is_string($this->subtotalLegend)) {
            return $this->subtotalLegend;
        }

        return $this->subtotalLegend[$key] ?? 'Total';
    }
}
