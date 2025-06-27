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

namespace Rekalogika\PivotTable\Table;

/**
 * Represents a HTML tabel cell (<td> or <th>).
 */
interface Cell extends Element
{
    public function getColumnSpan(): int;

    public function getRowSpan(): int;

    public function getContent(): mixed;
}
