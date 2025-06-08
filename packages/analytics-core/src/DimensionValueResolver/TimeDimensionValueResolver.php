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

use Rekalogika\Analytics\Contracts\Summary\HierarchicalDimensionValueResolver;
use Rekalogika\Analytics\Contracts\Summary\SourceContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Exception\InvalidArgumentException;

final readonly class TimeDimensionValueResolver implements HierarchicalDimensionValueResolver
{
    public function __construct(
        private TimeFormat $format,
    ) {}

    #[\Override]
    public function getDQL(
        object $input,
        SourceContext $context,
    ): string {
        if (!$input instanceof ValueResolver) {
            throw new InvalidArgumentException(\sprintf(
                'Expected instance of "%s", got "%s"',
                ValueResolver::class,
                get_debug_type($input),
            ));
        }

        return \sprintf(
            "REKALOGIKA_DATETIME_TO_SUMMARY_INTEGER(%s, '%s', '%s', '%s')",
            $input->getDQL($context),
            $context->getDimensionMetadata()->getSourceTimeZone()->getName(),
            $context->getDimensionMetadata()->getSummaryTimeZone()->getName(),
            $this->format->value,
        );
    }
}
