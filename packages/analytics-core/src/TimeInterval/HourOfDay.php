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

namespace Rekalogika\Analytics\TimeInterval;

use Rekalogika\Analytics\RecurringTimeInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HourOfDay implements RecurringTimeInterval
{
    use TimeIntervalTrait;

    // @phpstan-ignore constructor.unusedParameter
    private function __construct(
        private int $databaseValue,
        private \DateTimeZone $timeZone,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->databaseValue;
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
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
