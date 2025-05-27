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

namespace Rekalogika\Analytics\SimpleQueryBuilder\Path;

final readonly class Alias implements \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        return '*';
    }

    public function getName(): string
    {
        return '*';
    }

    public function getClassCast(): null
    {
        return null;
    }
}
