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
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Rekalogika\Analytics\Time\TimeBin as TimeBinInterface;
use Rekalogika\Analytics\Time\TimeBinType;

/**
 * @implements UserValueTransformer<RecurringTimeBin|int,TimeBinInterface|RecurringTimeBin>
 */
final readonly class TimeBin implements
    ValueResolver,
    HierarchyAware,
    UserValueTransformer
{
    public function __construct(
        private TimeBinType $type,
        private ?ValueResolver $input = null,
    ) {}

    #[\Override]
    public function withInput(ValueResolver $input): static
    {
        return new static(
            type: $this->type,
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
            $this->type->value,
        );
    }

    #[\Override]
    public function getUserValue(
        mixed $rawValue,
        ValueTransformerContext $context,
    ): mixed {
        if ($rawValue === null || $rawValue instanceof RecurringTimeBin) {
            return $rawValue;
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (!\is_int($rawValue)) {
            throw new InvalidArgumentException(\sprintf(
                'Expected integer, but got %s',
                get_debug_type($rawValue),
            ));
        }

        $metadata = $context->getPropertyMetadata();

        if ($metadata instanceof DimensionMetadata || $metadata instanceof DimensionPropertyMetadata) {
            $timeZone = $metadata->getSummaryTimeZone();
        } else {
            $timeZone = new \DateTimeZone('UTC');
        }

        $binClass = $this->type->getBinClass();

        if (!is_a($binClass, TimeBinInterface::class, true)) {
            throw new InvalidArgumentException(\sprintf(
                'The class "%s" is not a valid TimeBin class.',
                $binClass,
            ));
        }

        return $binClass::createFromDatabaseValue($rawValue)
            ->withTimeZone($timeZone);
    }
}
