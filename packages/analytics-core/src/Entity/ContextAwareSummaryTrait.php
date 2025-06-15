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

namespace Rekalogika\Analytics\Entity;

use Rekalogika\Analytics\Contracts\Context\SummaryContext;
use Rekalogika\Analytics\Contracts\Summary\ContextAwareSummary;

/**
 * @phpstan-require-implements ContextAwareSummary
 */
trait ContextAwareSummaryTrait
{
    private SummaryContext $context;

    final public function setContext(SummaryContext $context): void
    {
        $this->context = $context;
    }

    protected function getContext(): SummaryContext
    {
        return $this->context;
    }
}
