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
use Rekalogika\Analytics\Frontend\Formatter\Unsupported;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class NumberFormatStringifier implements Stringifier
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    #[\Override]
    public function toString(mixed $input): string
    {
        if (!\is_int($input) && !\is_float($input)) {
            throw new Unsupported();
        }

        $locale = $this->translator->getLocale();
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);

        // @phpstan-ignore method.internalClass
        $result = $formatter->format($input);

        if (!\is_string($result)) {
            throw new Unsupported();
        }

        return $result;
    }
}
