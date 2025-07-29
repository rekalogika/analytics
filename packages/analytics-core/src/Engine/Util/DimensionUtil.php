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

namespace Rekalogika\Analytics\Engine\Util;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Comparable;
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
            if (! $b->hasKey($name)) {
                return false;
            }

            if (! self::isDimensionSame($dimension, $b->getByKey($name))) {
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

    /**
     * @template T of Dimension
     * @param T $a
     * @param T $b
     * @return -1|0|1
     */
    public static function compare(Dimension $a, Dimension $b): int
    {
        if ($a::class !== $b::class) {
            throw new UnexpectedValueException(\sprintf(
                'Cannot compare "%s" with "%s".',
                $a::class,
                $b::class,
            ));
        }

        /** @psalm-suppress MixedAssignment */
        $aMember = $a->getMember();
        /** @psalm-suppress MixedAssignment */
        $bMember = $b->getMember();
        /** @psalm-suppress MixedAssignment */
        $aRawMember = $a->getRawMember();
        /** @psalm-suppress MixedAssignment */
        $bRawMember = $b->getRawMember();

        // check null in raw member
        if ($aRawMember === null) {
            if ($bRawMember === null) {
                return 0; // Both are null
            }

            // null is considered less than any non-null value
            return 1; // $a is less than $b
        } elseif ($bRawMember === null) {
            // Any non-null value is considered greater than null
            return -1; // $a is greater than $b;
        }

        // check null in member
        if ($aMember === null) {
            if ($bMember === null) {
                return 0; // Both are null
            }

            // null is considered less than any non-null value
            return 1; // $a is less than $b
        } elseif ($bMember === null) {
            // Any non-null value is considered greater than null
            return -1; // $a is greater than $b
        }

        if ($aMember instanceof Comparable && $bMember instanceof Comparable) {
            return $aMember::compare($aMember, $bMember);
        }

        return $a->getRawMember() <=> $b->getRawMember();
    }

    /**
     * @template K or array-key
     * @template V of Dimension
     * @param array<K,V> $dimensions
     * @return array<K,V>
     */
    public static function sort(array $dimensions, Order $order): array
    {
        if ($dimensions === []) {
            return $dimensions;
        }

        uasort(
            $dimensions,
            static function (
                Dimension $a,
                Dimension $b,
            ) use ($order): int {
                $comparison = self::compare($a, $b);

                return $order === Order::Ascending ? $comparison : -$comparison;
            },
        );

        return $dimensions;
    }
}
