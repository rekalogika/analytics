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

namespace Rekalogika\Analytics\Frontend\Formatter;

interface Numberifier
{
    /**
     * @throws ValueNotSupportedException If this numberifier cannot handle the input type.
     */
    public function toNumber(mixed $input): int|float;
}
