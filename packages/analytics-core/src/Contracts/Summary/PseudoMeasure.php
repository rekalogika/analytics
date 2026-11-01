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

namespace Rekalogika\Analytics\Contracts\Summary;

use Rekalogika\Analytics\Contracts\Context\PseudoMeasureContext;

/**
 * Used in place of an aggregate function to indicate that a measure is a
 * virtual measure that has its own custom handling in the framework.
 *
 * A pseudo-measure has no column in the summary table and no DQL expression.
 * Instead, the framework calls `createPseudoMeasure()` in PHP when the
 * measures of a cell are created. For example, `CoordinatesMeasure` returns
 * the coordinates of the cell as the measure's value.
 */
interface PseudoMeasure
{
    /**
     * Returns the value of the measure for the cell described by the context.
     */
    public function createPseudoMeasure(PseudoMeasureContext $context): mixed;
}
