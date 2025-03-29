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

use Rekalogika\Analytics\Contracts\Summary\DimensionValueResolver;
use Rekalogika\Analytics\Contracts\Summary\DimensionValueResolverContext;

final readonly class TimeDimensionValueResolver implements DimensionValueResolver
{
    public function __construct(
        private TimeFormat $format,
    ) {}

    #[\Override]
    public function getDQL(
        string $input,
        DimensionValueResolverContext $context,
    ): string {
        return \sprintf(
            "REKALOGIKA_DATETIME_TO_SUMMARY_INTEGER(%s, '%s', '%s', '%s')",
            $input,
            $context->getDimensionMetadata()->getSourceTimeZone()->getName(),
            $context->getDimensionMetadata()->getSummaryTimeZone()->getName(),
            $this->format->value,
        );

    }
}
