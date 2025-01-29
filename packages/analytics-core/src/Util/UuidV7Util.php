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

use Symfony\Component\Uid\Uuid;

final readonly class UuidV7Util
{
    private function __construct() {}

    public static function getNilOfDateTime(\DateTimeInterface $time): string
    {
        $time = (int) $time->format('U');
        $time *= 1000;
        $time = dechex($time);

        $str = substr_replace(\sprintf(
            '%012s-0000-0000-000000000000',
            $time,
        ), '-', 8, 0);

        return (string) new Uuid($str, false);
    }

    public static function getMaxOfDateTime(\DateTimeInterface $time): string
    {
        $time = (int) $time->format('U');
        $time *= 1000;
        $time = dechex($time);

        $str = substr_replace(\sprintf(
            '%012s-ffff-ffff-ffffffffffff',
            $time,
        ), '-', 8, 0);

        return (string) new Uuid($str, false);
    }

    public static function getNilOfInteger(int $input): string
    {
        $input >>= 16;

        $str = substr_replace(\sprintf(
            '%012x-0000-0000-000000000000',
            $input,
        ), '-', 8, 0);

        return (string) new Uuid($str, false);
    }

    public static function getMaxOfInteger(int $input): string
    {
        $input >>= 16;

        $str = substr_replace(\sprintf(
            '%012x-ffff-ffff-ffffffffffff',
            $input,
        ), '-', 8, 0);

        return (string) new Uuid($str, false);
    }
}
