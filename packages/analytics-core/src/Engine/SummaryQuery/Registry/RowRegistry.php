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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Registry;

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultRow;

final class RowRegistry
{
    /**
     * @var \WeakMap<DefaultCell,DefaultRow>
     */
    private \WeakMap $rows;

    /**
     * @param list<string> $dimensionality
     */
    public function __construct(
        private array $dimensionality,
    ) {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->rows = new \WeakMap();
    }

    public function getRowByCell(DefaultCell $cell): DefaultRow
    {
        if (isset($this->rows[$cell])) {
            /** @var DefaultRow */
            return $this->rows[$cell] ?? throw new UnexpectedValueException(
                'Row for the given cell is not found in the registry.',
            );
        }

        $row = new DefaultRow(
            cell: $cell,
            dimensionality: $this->dimensionality,
        );

        return $this->rows[$cell] = $row;
    }
}
