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

use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\DimensionValueResolver\TimeFormat;
use Rekalogika\Analytics\SummaryManager\Query\QueryContext;

final readonly class TimeDimensionValueResolver implements ValueResolver
{
    public function __construct(
        private string $property,
        private TimeFormat $format,
        private \DateTimeZone $sourceTimeZone = new \DateTimeZone('UTC'),
        private \DateTimeZone $summaryTimeZone = new \DateTimeZone('UTC'),
    ) {}

    #[\Override]
    public function getDQL(QueryContext $context): string
    {
        return \sprintf(
            "REKALOGIKA_DATETIME_TO_SUMMARY_INTEGER(%s, '%s', '%s', '%s')",
            $context->resolvePath($this->property),
            $this->sourceTimeZone->getName(),
            $this->summaryTimeZone->getName(),
            $this->format->value,
        );
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [$this->property];
    }
}
