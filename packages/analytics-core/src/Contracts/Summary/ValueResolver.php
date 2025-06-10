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

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;

interface ValueResolver
{
    /**
     * Returns a DQL expression to transform the source value to the value
     * expected in the summary table.
     */
    public function getExpression(SourceQueryContext $context): string;

    /**
     * The properties of the source entity that are involved in the calculation.
     * Used by Analytics to determine if a change in the source entity requires
     * a recalculation of the summary.
     *
     * If the value resolver takes another value resolver as input, it should
     * return the involved properties of the input value resolver.
     *
     * @return list<string>
     */
    public function getInvolvedProperties(): array;
}
