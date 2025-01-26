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

namespace Rekalogika\Analytics\TimeDimensionHierarchy;

final class HourOfDay implements RecurringInterval
{
    use CacheTrait;

    // @phpstan-ignore constructor.unusedParameter
    private function __construct(
        private int $databaseValue,
        private \DateTimeZone $timeZone,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%02d:00-%02d:59',
            $this->databaseValue,
            $this->databaseValue,
        );
    }

    public function getTimezone(): \DateTimeZone
    {
        return $this->timeZone;
    }
}
