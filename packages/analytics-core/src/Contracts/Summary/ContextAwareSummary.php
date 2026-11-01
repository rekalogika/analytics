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

use Rekalogika\Analytics\Contracts\Context\SummaryContext;

/**
 * If a summary is context-aware, it will get an instance of SummaryContext
 * when the summary is created.
 *
 * The framework instantiates the summary entity from each row of a query
 * result, and calls `setContext()` before any of its getters are called. The
 * context lets the getters return user-facing values computed from the raw
 * values of the row, e.g. a `TimeBin` object instead of the integer stored in
 * the database. Use `ContextAwareSummaryTrait` for a ready-made
 * implementation of this interface.
 *
 * @see SummaryContext
 */
interface ContextAwareSummary
{
    /**
     * Receives the context from the framework.
     */
    public function setContext(SummaryContext $context): void;
}
