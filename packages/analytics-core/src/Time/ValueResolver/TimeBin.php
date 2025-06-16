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

namespace Rekalogika\Analytics\Time\ValueResolver;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Context\ValueTransformerContext;
use Rekalogika\Analytics\Contracts\Hierarchy\HierarchyAware;
use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionPropertyMetadata;
use Rekalogika\Analytics\Time\TimeBin as TimeBinInterface;
use Rekalogika\Analytics\Time\TimeBinType;

/**
 * @implements UserValueTransformer<TimeBinInterface,TimeBinInterface>
 */
final readonly class TimeBin implements
    ValueResolver,
    HierarchyAware,
    UserValueTransformer
{
    public function __construct(
        private TimeBinType $format,
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
    public function getExpression(
        SourceQueryContext $context,
    ): string {
        if (!$this->input instanceof ValueResolver) {
            throw new InvalidArgumentException(\sprintf(
                'TimeBin requires an input ValueResolver, but got %s',
                get_debug_type($this->input),
            ));
        }

        return \sprintf(
            "REKALOGIKA_TIME_BIN(%s, '%s', '%s', '%s')",
            $this->input->getExpression($context),
            $context->getDimensionMetadata()->getSourceTimeZone()->getName(),
            $context->getDimensionMetadata()->getSummaryTimeZone()->getName(),
            $this->format->value,
        );
    }

    #[\Override]
    public function getUserValue(
        mixed $rawValue,
        ValueTransformerContext $context,
    ): mixed {
        if (!$rawValue instanceof TimeBinInterface) {
            throw new InvalidArgumentException(\sprintf(
                'Expected TimeBinInterface, but got %s',
                get_debug_type($rawValue),
            ));
        }

        $metadata = $context->getPropertyMetadata();

        if ($metadata instanceof DimensionMetadata || $metadata instanceof DimensionPropertyMetadata) {
            $timeZone = $metadata->getSummaryTimeZone();
        } else {
            $timeZone = new \DateTimeZone('UTC');
        }

        return $rawValue->withTimeZone($timeZone);
    }
}
