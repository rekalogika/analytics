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

namespace Rekalogika\Analytics\ValueResolver;

use Rekalogika\Analytics\Contracts\Summary\HierarchyAwareValueResolver;
use Rekalogika\Analytics\Contracts\Summary\SourceContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;

final readonly class TimeDimensionValueResolver implements HierarchyAwareValueResolver
{
    private ?ValueResolver $input;

    public function __construct(
        private TimeFormat $format,
        null|string|ValueResolver $input = null,
    ) {
        if (\is_string($input)) {
            $input = new PropertyValueResolver($input);
        }

        $this->input = $input;
    }

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
