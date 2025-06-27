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
 * Represents a HTML table (<table>).
 *
 * @extends \Traversable<TableSection>
 */
interface Table extends \Traversable, \Countable, Element
{
    public function getRows(): RowGroup;
}
