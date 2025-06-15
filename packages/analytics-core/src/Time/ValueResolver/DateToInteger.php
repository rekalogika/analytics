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
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Exception\LogicException;

/**
 * Convert source date into integer. Epoch is 1970-01-01.
 *
 * @implements PartitionValueResolver<\DateTimeInterface>
 */
final readonly class DateToInteger implements PartitionValueResolver
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
            "DATE_DIFF(%s, '1970-01-01')",
            $context->resolve($this->property),
        );
    }

    #[\Override]
    public function transformSourceValueToSummaryValue(mixed $value): int
    {
        if (\is_string($value)) {
            $value = new \DateTimeImmutable($value);
        }

        if ($value->getTimestamp() < 0) {
            throw new LogicException('Date cannot be before epoch.');
        }

        $days = $value->diff(new \DateTimeImmutable('1970-01-01'))->format('%a');

        return \intval($days);
    }

    #[\Override]
    public function transformSummaryValueToSourceValue(int $value): \DateTimeInterface
    {
        if ($value < 0) {
            throw new LogicException('Date cannot be before epoch.');
        }

        $date = new \DateTimeImmutable('1970-01-01');
        $date = $date->add(new \DateInterval(\sprintf('P%dD', $value)));

        return $date;
    }
}
