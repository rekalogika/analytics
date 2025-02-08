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

use Symfony\Contracts\Translation\TranslatorInterface;

final class DayOfYear implements RecurringInterval
{
    use CacheTrait;

    private function __construct(
        private int $databaseValue,
        // @phpstan-ignore property.onlyWritten
        private \DateTimeZone $timeZone,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->databaseValue;
    }

    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return (string) $this->databaseValue;
    }
}
