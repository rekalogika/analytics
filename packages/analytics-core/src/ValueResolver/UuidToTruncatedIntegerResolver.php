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

use Rekalogika\Analytics\ReversibleValueResolver;
use Rekalogika\Analytics\SummaryManager\Query\QueryContext;
use Rekalogika\Analytics\Util\UuidV7Util;
use Rekalogika\Analytics\ValueRangeResolver;

/**
 * Truncate source value in UUID format to 64-bit integer.
 */
final readonly class UuidToTruncatedIntegerResolver implements ReversibleValueResolver, ValueRangeResolver
{
    public function __construct(
        private string $property,
    ) {}

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [$this->property];
    }

    #[\Override]
    public function getDQL(QueryContext $context): string
    {
        return \sprintf(
            'REKALOGIKA_TRUNCATE_UUID_TO_BIGINT(%s)',
            $context->resolvePath($this->property),
        );
    }

    #[\Override]
    public function getMinDQL(QueryContext $context): string
    {
        return \sprintf(
            "MIN(REKALOGIKA_CAST(%s, 'TEXT'))",
            $context->resolvePath($this->property),
        );
    }

    #[\Override]
    public function getMaxDQL(QueryContext $context): string
    {
        return \sprintf(
            "MAX(REKALOGIKA_CAST(%s, 'TEXT'))",
            $context->resolvePath($this->property),
        );
    }

    /**
     * transform from source value to summary value (uuid to integer)
     */
    #[\Override]
    public function transform(mixed $value): mixed
    {
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('Value must be a string, got %s', get_debug_type($value)));
        }

        $value = str_replace('-', '', $value);
        $value = hexdec(substr($value, 0, 12)); // first 48 bits

        if (\is_float($value)) {
            throw new \InvalidArgumentException('Cannot convert UUID to integer. Make sure you are using a 64-bit system.');
        }

        return $value;
    }

    /**
     * Transform from summary value to source value (integer to uuid)
     */
    #[\Override]
    public function reverseTransform(mixed $value): string
    {
        if (!\is_int($value)) {
            throw new \InvalidArgumentException('Value must be an integer');
        }

        $value <<= 16;

        return UuidV7Util::getNilOfInteger($value);
    }
}
