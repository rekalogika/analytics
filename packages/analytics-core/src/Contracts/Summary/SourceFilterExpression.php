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

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;

/**
 * A condition in the `filter` of a summary that cannot be expressed as a
 * Doctrine Collections `Comparison`, e.g. `INSTANCE OF`, or a comparison of
 * two properties. It can be combined with comparisons in a
 * `CompositeExpression`.
 *
 * The framework does not call `visit()`, it calls `getExpression()` when
 * applying the filter to a query against the source entities.
 */
interface SourceFilterExpression extends Expression
{
    /**
     * Returns a DQL condition, e.g. `root.time > root.shipped`.
     *
     * @param SourceQueryContext $context Resolves property paths of the source
     * entity into DQL, and binds parameters.
     */
    public function getExpression(SourceQueryContext $context): string;

    /**
     * The property paths of the source entity that the condition reads. A
     * change to one of them refreshes the summary.
     *
     * @return list<string>
     */
    public function getInvolvedProperties(): array;
}
