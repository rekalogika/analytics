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

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\Dimensions;

final readonly class DimensionUtil
{
    private function __construct() {}

    public static function getDimensionSignature(Dimension $dimension): string
    {
        $key = $dimension->getKey();
        /** @psalm-suppress MixedAssignment */
        $rawMember = $dimension->getRawMember();

        if (\is_object($rawMember)) {
            return hash('xxh128', serialize([
                $key,
                spl_object_id($rawMember),
            ]));
        }

        return hash('xxh128', serialize([$key, $rawMember]));
    }

    /**
     * @param iterable<Dimension> $dimensions
     */
    public static function getDimensionsSignature(iterable $dimensions): string
    {
        $signatures = [];

        foreach ($dimensions as $dimension) {
            $signatures[] = self::getDimensionSignature($dimension);
        }

        return hash('xxh128', serialize($signatures));
    }

    public static function isDimensionSame(Dimension $a, Dimension $b): bool
    {
        if ($a::class !== $b::class) {
            return false;
        }

        if ($a->getKey() !== $b->getKey()) {
            return false;
        }

        if ($a->getRawMember() !== $b->getRawMember()) {
            return false;
        }

        return true;
    }

    public static function isDimensionsSame(Dimensions $a, Dimensions $b): bool
    {
        if ($a->count() !== $b->count()) {
            return false;
        }

        foreach ($a as $key => $dimension) {
            if (! $b->has($key)) {
                return false;
            }

            if (! self::isDimensionSame($dimension, $b->get($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<Dimension> $a
     * @param list<Dimension> $b
     */
    public static function isDimensionsArraySame(array $a, array $b): bool
    {
        if (\count($a) !== \count($b)) {
            return false;
        }

        foreach ($a as $key => $dimension) {
            if (! isset($b[$key])) {
                return false;
            }

            if (! self::isDimensionSame($dimension, $b[$key])) {
                return false;
            }
        }

        return true;
    }
}
