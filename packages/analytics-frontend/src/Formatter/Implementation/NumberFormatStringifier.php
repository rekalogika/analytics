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

namespace Rekalogika\Analytics\Frontend\Formatter\Implementation;

use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NumberFormatStringifier implements Stringifier
{
    /**
     * Formatters for numbers with an absolute value of at least 1, keyed by
     * locale.
     *
     * @var array<string,\NumberFormatter>
     */
    private array $formatters = [];

    /**
     * Formatters for fractions (0 < |x| < 1), keyed by locale.
     *
     * @var array<string,\NumberFormatter>
     */
    private array $fractionFormatters = [];

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    #[\Override]
    public function toString(mixed $input): string
    {
        if (!\is_int($input) && !\is_float($input)) {
            throw new ValueNotSupportedException();
        }

        $locale = $this->translator->getLocale();

        if (\is_float($input) && $input !== 0.0 && abs($input) < 1) {
            $formatter = $this->getFractionFormatter($locale);
        } else {
            $formatter = $this->getFormatter($locale);
        }

        // @phpstan-ignore method.internalClass
        $result = $formatter->format($input);

        if (!\is_string($result)) {
            throw new ValueNotSupportedException();
        }

        return $result;
    }

    private function getFormatter(string $locale): \NumberFormatter
    {
        return $this->formatters[$locale]
            ??= new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
    }

    /**
     * The default formatter has a maximum of 3 fraction digits, which would
     * render a small number like 0.000123 as 0. Use significant digits
     * instead for fractions.
     */
    private function getFractionFormatter(string $locale): \NumberFormatter
    {
        if (isset($this->fractionFormatters[$locale])) {
            return $this->fractionFormatters[$locale];
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        // @phpstan-ignore method.internalClass
        $formatter->setAttribute(\NumberFormatter::SIGNIFICANT_DIGITS_USED, 1);
        // @phpstan-ignore method.internalClass
        $formatter->setAttribute(\NumberFormatter::MAX_SIGNIFICANT_DIGITS, 3);

        return $this->fractionFormatters[$locale] = $formatter;
    }
}
