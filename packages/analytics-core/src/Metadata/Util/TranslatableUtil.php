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

namespace Rekalogika\Analytics\Metadata\Util;

use Rekalogika\Analytics\Contracts\Translation\LiteralString;
use Symfony\Contracts\Translation\TranslatableInterface;

final class TranslatableUtil
{
    private function __construct() {}

    /**
     * @return ($translatable is null ? null : TranslatableInterface)
     */
    public static function normalize(
        null|string|TranslatableInterface $translatable,
    ): ?TranslatableInterface {
        if ($translatable === null) {
            return null;
        }

        if (\is_string($translatable)) {
            return new LiteralString($translatable);
        }

        return $translatable;
    }
}
