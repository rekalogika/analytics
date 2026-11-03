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

namespace Rekalogika\Analytics\Uuid\Util;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class UuidV7Util
{
    /**
     * The largest value of the 48-bit timestamp field of a UUIDv7, in
     * milliseconds since the Unix epoch.
     */
    private const MAX_TIMESTAMP = 0xFFFFFFFFFFFF;

    private function __construct() {}

    /**
     * Returns the smallest UUIDv7 having the same millisecond timestamp as the
     * given datetime.
     *
     * @throws InvalidArgumentException If the datetime is before the Unix
     * epoch, or beyond the range of the 48-bit UUIDv7 timestamp.
     */
    public static function getNilOfDateTime(\DateTimeInterface $time): string
    {
        return self::createUuid(
            prefix: self::getTimestamp($time),
            suffix: '0000-0000-000000000000',
        );
    }

    /**
     * Returns the largest UUIDv7 having the same millisecond timestamp as the
     * given datetime.
     *
     * @throws InvalidArgumentException If the datetime is before the Unix
     * epoch, or beyond the range of the 48-bit UUIDv7 timestamp.
     */
    public static function getMaxOfDateTime(\DateTimeInterface $time): string
    {
        return self::createUuid(
            prefix: self::getTimestamp($time),
            suffix: 'ffff-ffff-ffffffffffff',
        );
    }

    /**
     * Returns the smallest UUID whose first 48 bits are the upper 48 bits of
     * the given 64-bit integer.
     */
    public static function getNilOfInteger(int $input): string
    {
        return self::createUuid(
            prefix: ($input >> 16) & self::MAX_TIMESTAMP,
            suffix: '0000-0000-000000000000',
        );
    }

    /**
     * Returns the largest UUID whose first 48 bits are the upper 48 bits of
     * the given 64-bit integer.
     */
    public static function getMaxOfInteger(int $input): string
    {
        return self::createUuid(
            prefix: ($input >> 16) & self::MAX_TIMESTAMP,
            suffix: 'ffff-ffff-ffffffffffff',
        );
    }

    /**
     * Returns the number of milliseconds since the Unix epoch.
     */
    private static function getTimestamp(\DateTimeInterface $time): int
    {
        $timestamp = $time->getTimestamp() * 1000
            + intdiv((int) $time->format('u'), 1000);

        if ($timestamp < 0 || $timestamp > self::MAX_TIMESTAMP) {
            throw new InvalidArgumentException(\sprintf(
                'Cannot convert the datetime "%s" to a UUIDv7 timestamp. It must not be earlier than the Unix epoch, or later than 10889-08-02T05:31:50.655Z.',
                $time->format('Y-m-d\TH:i:s.vP'),
            ));
        }

        return $timestamp;
    }

    /**
     * @param int $prefix The first 48 bits of the UUID
     * @param string $suffix The rest of the UUID, after the second dash
     */
    private static function createUuid(int $prefix, string $suffix): string
    {
        $str = substr_replace(\sprintf(
            '%012x-%s',
            $prefix,
            $suffix,
        ), '-', 8, 0);

        return (string) new Uuid($str, false);
    }
}
