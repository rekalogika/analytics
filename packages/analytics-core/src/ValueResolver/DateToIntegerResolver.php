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
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\SummaryManager\Query\QueryContext;

/**
 * Convert source date into integer. Epoch is 1970-01-01.
 */
final readonly class DateToIntegerResolver implements PartitionValueResolver
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
            "DATE_DIFF(%s, '1970-01-01')",
            $context->resolvePath($this->property),
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

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (!$date instanceof \DateTimeImmutable) {
            throw new InvalidArgumentException(\sprintf(
                'Unable to convert "%s" to date.',
                $value,
            ));
        }

        if ($date->getTimestamp() < 0) {
            throw new LogicException('Date cannot be before epoch.');
        }

        return $date->diff(new \DateTimeImmutable('1970-01-01'))->format('%a');
    }

    /**
     * Transform from summary value to source value (integer to datetime)
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

        if ($value < 0) {
            throw new LogicException('Date cannot be before epoch.');
        }

        $date = new \DateTimeImmutable('1970-01-01');
        $date = $date->add(new \DateInterval(\sprintf('P%dD', $value)));

        return $date->format('Y-m-d');
    }
}
