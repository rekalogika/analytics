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

namespace Rekalogika\Analytics\Time\Util;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A date formatted in the long format of the locale when it is translated,
 * e.g. "3 February 2025" or "3. Februar 2025". Used as a parameter of a
 * translatable message, so the date follows the locale of the message.
 *
 * @internal
 */
final readonly class TranslatableDate implements TranslatableInterface, \Stringable
{
    public function __construct(
        private \DateTimeInterface $date,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return $this->date->format('Y-m-d');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $locale ??= $translator->getLocale();

        $intlDateFormatter = new \IntlDateFormatter(
            locale: $locale,
            dateType: \IntlDateFormatter::LONG,
            timeType: \IntlDateFormatter::NONE,
            timezone: $this->date->getTimezone(),
        );

        $formatted = $intlDateFormatter->format($this->date);

        if (!\is_string($formatted)) {
            return $this->__toString();
        }

        return $formatted;
    }
}
