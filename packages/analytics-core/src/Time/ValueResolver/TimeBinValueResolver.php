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

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Context\ValueTransformerContext;
use Rekalogika\Analytics\Contracts\DimensionGroup\DimensionGroupAware;
use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\Time\Util\TimeZoneUtil;

/**
 * @implements UserValueTransformer<RecurringTimeBin|int,TimeBin>
 */
final readonly class TimeBinValueResolver implements
    ValueResolver,
    DimensionGroupAware,
    UserValueTransformer
{
    /**
     * @param class-string<TimeBin> $binClass
     * @param ValueResolver|null $input
     */
    public function __construct(
        private string $binClass,
        private ?ValueResolver $input = null,
    ) {}

    #[\Override]
    public function withInput(ValueResolver $input): static
    {
        return new static(
            binClass: $this->binClass,
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

        [$sourceTimeZone, $summaryTimeZone] = TimeZoneUtil::resolveTimeZones(
            $context->getDimensionMetadata(),
        );

        $class = $this->binClass;

        return $class::getDQLExpression(
            $this->input->getExpression($context),
            $sourceTimeZone,
            $summaryTimeZone,
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

        $dimensionMetadata = $context->getDimensionMetadata();

        [$sourceTimeZone, $summaryTimeZone] = TimeZoneUtil::resolveTimeZones(
            $dimensionMetadata,
        );

        $binClass = $this->binClass;

        if (!is_a($binClass, MonotonicTimeBin::class, true)) {
            throw new InvalidArgumentException(\sprintf(
                'The class "%s" is not a valid TimeBin class.',
                $binClass,
            ));
        }

        /** @var class-string<MonotonicTimeBin> $binClass */

        return $binClass::createFromDatabaseValue($rawValue)
            ->withTimeZone($summaryTimeZone);
    }

    /**
     * @return class-string<TimeBin>
     */
    public function getTypeClass(): string
    {
        return $this->binClass;
    }
}
