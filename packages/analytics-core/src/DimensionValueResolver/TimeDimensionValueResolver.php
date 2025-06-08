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

use Rekalogika\Analytics\Contracts\Summary\HierarchyAwareValueResolver;
use Rekalogika\Analytics\Contracts\Summary\SourceContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Exception\InvalidArgumentException;

final readonly class TimeDimensionValueResolver implements HierarchyAwareValueResolver
{
    public function __construct(
        private TimeFormat $format,
        private ?ValueResolver $input = null,
    ) {}

    #[\Override]
    public function withInput(ValueResolver $input): static
    {
        return new static(
            format: $this->format,
            input: $input,
        );
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->input?->getInvolvedProperties() ?? [];
    }

    #[\Override]
    public function getDQL(
        SourceContext $context,
    ): string {
        if (!$this->input instanceof ValueResolver) {
            throw new InvalidArgumentException(\sprintf(
                'TimeDimensionValueResolver requires an input ValueResolver, but got %s',
                get_debug_type($this->input),
            ));
        }

        return \sprintf(
            "REKALOGIKA_DATETIME_TO_SUMMARY_INTEGER(%s, '%s', '%s', '%s')",
            $this->input->getDQL($context),
            $context->getDimensionMetadata()->getSourceTimeZone()->getName(),
            $context->getDimensionMetadata()->getSummaryTimeZone()->getName(),
            $this->format->value,
        );
    }
}
