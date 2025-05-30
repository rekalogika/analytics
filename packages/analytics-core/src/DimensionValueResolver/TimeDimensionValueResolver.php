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

namespace Rekalogika\Analytics\DimensionValueResolver;

use Rekalogika\Analytics\Contracts\Summary\Context;
use Rekalogika\Analytics\Contracts\Summary\HierarchicalDimensionValueResolver;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

final readonly class TimeDimensionValueResolver implements HierarchicalDimensionValueResolver
{
    public function __construct(
        private TimeFormat $format,
    ) {}

    #[\Override]
    public function getDQL(
        ValueResolver $input,
        Context $context,
    ): string {
        return \sprintf(
            "REKALOGIKA_DATETIME_TO_SUMMARY_INTEGER(%s, '%s', '%s', '%s')",
            $input->getDQL($context),
            $context->getDimensionMetadata()->getSourceTimeZone()->getName(),
            $context->getDimensionMetadata()->getSummaryTimeZone()->getName(),
            $this->format->value,
        );

    }
}
