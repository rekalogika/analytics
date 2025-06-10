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
 */
interface ContextAwareSummary
{
    public function setContext(SummaryContext $context): void;
}
