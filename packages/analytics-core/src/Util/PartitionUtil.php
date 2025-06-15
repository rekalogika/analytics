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

use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\SummaryManager\PartitionRange;

final readonly class PartitionUtil
{
    private function __construct() {}

    /**
     * @param Partition<mixed> $partitionClass
     */
    public static function getLowerLevel(Partition $partition): ?int
    {
        $levels = $partition::getAllLevels();
        $level = $partition->getLevel();

        $index = array_search($level, $levels, true);

        if ($index === false) {
            throw new UnexpectedValueException(\sprintf(
                'The level "%s" is not found in the partition levels.',
                $level,
            ));
        }

        return $levels[$index + 1] ?? null;
    }

    public static function getHigherLevel(Partition $partition): ?int
    {
        $levels = $partition::getAllLevels();
        $level = $partition->getLevel();

        $index = array_search($level, $levels, true);

        if ($index === false) {
            throw new UnexpectedValueException(\sprintf(
                'The level "%s" is not found in the partition levels.',
                $level,
            ));
        }

        if ($index === 0) {
            return null;
        }

        return $levels[$index - 1] ?? null;
    }

    /**
     * @param class-string<Partition<mixed>> $partitionClass
     */
    public static function getLowestLevel(string $partitionClass): int
    {
        $levels = $partitionClass::getAllLevels();

        return $levels[\count($levels) - 1];
    }

    /**
     * @param class-string<Partition<mixed>> $partitionClass
     */
    public static function getHighestLevel(string $partitionClass): int
    {
        $levels = $partitionClass::getAllLevels();

        return $levels[0];
    }

    /**
     * @param Partition<mixed>|PartitionRange $partition
     */
    public static function isLowestLevel(Partition|PartitionRange $partition): bool
    {
        if ($partition instanceof PartitionRange) {
            $partition = $partition->getStart();
        }

        return $partition->getLevel() === self::getLowestLevel($partition::class);
    }


    public static function printRange(PartitionRange $partitionRange): string
    {
        return \sprintf(
            '%s - %s',
            (string) $partitionRange->getStart(),
            (string) $partitionRange->getEnd(),
        );
    }

    /**
     * @param Partition<mixed> $partition
     */
    public static function getLowerLevelPartitionRange(
        Partition $partition,
    ): ?PartitionRange {
        $level = self::getLowerLevel($partition);

        if ($level === null) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $lowerBoundSourceValue = $partition->getLowerBound();
        /** @psalm-suppress MixedAssignment */
        $upperBoundSourceValue = $partition->getUpperBound();

        $current = $partition::createFromSourceValue($lowerBoundSourceValue, $level);
        $end = $partition::createFromSourceValue($upperBoundSourceValue, $level);

        return new PartitionRange($current, $end);
    }

    /**
     * @template T of mixed
     * @param Partition<T> $a
     * @param Partition<T> $b
     */
    public static function isEqual(Partition $a, Partition $b): bool
    {
        return $a->getLevel() === $b->getLevel()
            && $a->getKey() === $b->getKey();
    }

    /**
     * @template T of mixed
     * @param Partition<T> $a
     * @param Partition<T> $b
     */
    public static function isLessThan(Partition $a, Partition $b): bool
    {
        if ($a->getLevel() !== $b->getLevel()) {
            throw new UnexpectedValueException(\sprintf(
                'The partitions must have the same level. "%s" and "%s" given.',
                $a->getLevel(),
                $b->getLevel(),
            ));
        }

        return $a->getKey() < $b->getKey();
    }

    /**
     * @template T of mixed
     * @param Partition<T> $a
     * @param Partition<T> $b
     */
    public static function isGreaterThan(Partition $a, Partition $b): bool
    {
        if ($a->getLevel() !== $b->getLevel()) {
            throw new UnexpectedValueException(\sprintf(
                'The partitions must have the same level. "%s" and "%s" given.',
                $a->getLevel(),
                $b->getLevel(),
            ));
        }

        return $a->getKey() > $b->getKey();
    }

    /**
     * @template T of mixed
     * @param Partition<T> $a
     * @param Partition<T> $b
     */
    public static function isEqualOrLessThan(Partition $a, Partition $b): bool
    {
        if ($a->getLevel() !== $b->getLevel()) {
            throw new UnexpectedValueException(\sprintf(
                'The partitions must have the same level. "%s" and "%s" given.',
                $a->getLevel(),
                $b->getLevel(),
            ));
        }

        return $a->getKey() <= $b->getKey();
    }

    /**
     * @template T of mixed
     * @param Partition<T> $a
     * @param Partition<T> $b
     */
    public static function isEqualOrGreaterThan(Partition $a, Partition $b): bool
    {
        if ($a->getLevel() !== $b->getLevel()) {
            throw new UnexpectedValueException(\sprintf(
                'The partitions must have the same level. "%s" and "%s" given.',
                $a->getLevel(),
                $b->getLevel(),
            ));
        }

        return $a->getKey() >= $b->getKey();
    }
}
