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

namespace Rekalogika\Analytics\Util;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translatable that simply returns a string, without translation.
 */
final class LiteralString implements TranslatableInterface, \Stringable
{
    public function __construct(
        private string $string,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return $this->string;
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->string;
    }
}
