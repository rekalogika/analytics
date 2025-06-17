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

namespace Rekalogika\Analytics\Uuid\ValueResolver;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Uuid\Util\UuidV7Util;

/**
 * Truncate the source value in UUID format to 48-bit integer.
 *
 * @implements PartitionValueResolver<string>
 */
final readonly class StringUuidToTruncatedInteger implements PartitionValueResolver
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
    public function getExpression(SourceQueryContext $context): string
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
    public function transformSourceValueToSummaryValue(mixed $value): int
    {
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
    public function transformSummaryValueToSourceValue(int $value): string
    {
        return UuidV7Util::getNilOfInteger($value <<= 16);
    }
}
