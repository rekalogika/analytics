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

use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Contracts\Summary\SourceContext;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Util\UuidV7Util;

/**
 * Truncate the source value in UUID format to 48-bit integer.
 */
final readonly class UuidToTruncatedIntegerResolver implements PartitionValueResolver
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
    public function getDQL(SourceContext $context): string
    {
        return \sprintf(
            'REKALOGIKA_TRUNCATE_UUID_TO_BIGINT(%s)',
            $context->resolve($this->property),
        );
    }

    /**
     * transform from source value to summary value (uuid to integer)
     */
    #[\Override]
    public function transformSourceValueToSummaryValue(mixed $value): mixed
    {
        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf(
                'Value must be a string, got "%s".',
                get_debug_type($value),
            ));
        }

        $value = str_replace('-', '', $value);
        $value = hexdec(substr($value, 0, 12)); // first 48 bits

        if (\is_float($value)) {
            throw new LogicException('Cannot convert UUID to integer. Make sure you are using a 64-bit system.');
        }

        return $value;
    }

    /**
     * Transform from summary value to source value (integer to uuid)
     */
    #[\Override]
    public function transformSummaryValueToSourceValue(mixed $value): string
    {
        if (!\is_int($value)) {
            throw new InvalidArgumentException(\sprintf(
                'Value must be an integer, got "%s".',
                get_debug_type($value),
            ));
        }

        $value <<= 16;

        return UuidV7Util::getNilOfInteger($value);
    }
}
