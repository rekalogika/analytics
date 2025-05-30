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

use Rekalogika\Analytics\Contracts\Summary\Context;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Util\UuidV7Util;
use Symfony\Component\Uid\UuidV7;

final readonly class UuidDateTimeValueResolver implements PartitionValueResolver
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
    public function getDQL(Context $context): string
    {
        return \sprintf(
            'REKALOGIKA_UUID_TO_DATETIME(%s)',
            $context->resolvePath($this->property),
        );
    }

    #[\Override]
    public function transformSummaryValueToSourceValue(mixed $value): string
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new InvalidArgumentException(\sprintf(
                'Value must be an instance of DateTimeInterface, "%s" given.',
                get_debug_type($value),
            ));
        }

        return UuidV7Util::getNilOfDateTime($value);
    }

    #[\Override]
    public function transformSourceValueToSummaryValue(mixed $value): mixed
    {
        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf(
                'Value must be a string, "%s" given.',
                get_debug_type($value),
            ));
        }

        $uuid = new UuidV7($value);

        return $uuid->getDateTime();
    }
}
