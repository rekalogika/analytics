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

namespace Rekalogika\Analytics\Core\Util;

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\Tuple;

final readonly class DimensionUtil
{
    private function __construct() {}

    public static function getDimensionSignature(Dimension $dimension): string
    {
        $name = $dimension->getName();
        /** @psalm-suppress MixedAssignment */
        $rawMember = $dimension->getRawMember();

        if (\is_object($rawMember)) {
            return hash('xxh128', serialize([
                $name,
                spl_object_id($rawMember),
            ]));
        }

        return hash('xxh128', serialize([$name, $rawMember]));
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

    public static function isDimensionSame(?Dimension $a, ?Dimension $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        if ($a::class !== $b::class) {
            return false;
        }

        if ($a->getName() !== $b->getName()) {
            return false;
        }

        return $a->getRawMember() === $b->getRawMember();
    }

    public static function isTupleSame(Tuple $a, Tuple $b): bool
    {
        if ($a->count() !== $b->count()) {
            return false;
        }

        foreach ($a as $name => $dimension) {
            if (! $b->has($name)) {
                return false;
            }

            if (! self::isDimensionSame($dimension, $b->getByName($name))) {
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

        foreach ($a as $name => $dimension) {
            if (! isset($b[$name])) {
                return false;
            }

            if (! self::isDimensionSame($dimension, $b[$name])) {
                return false;
            }
        }

        return true;
    }
}
