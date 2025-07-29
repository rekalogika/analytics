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

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\SummaryManager\PartitionRange;

final readonly class PartitionUtil
{
    private function __construct() {}

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
     * @param class-string<Partition> $partitionClass
     */
    public static function getLowestLevel(string $partitionClass): int
    {
        $levels = $partitionClass::getAllLevels();

        return $levels[\count($levels) - 1];
    }

    /**
     * @param class-string<Partition> $partitionClass
     */
    public static function getHighestLevel(string $partitionClass): int
    {
        $levels = $partitionClass::getAllLevels();

        return $levels[0];
    }

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

    public static function getLowerLevelPartitionRange(
        Partition $partition,
    ): ?PartitionRange {
        $level = self::getLowerLevel($partition);

        if ($level === null) {
            return null;
        }

        $lowerBoundSourceValue = $partition->getLowerBound();
        $upperBoundSourceValue = $partition->getUpperBound();

        $current = $partition::createFromSourceValue($lowerBoundSourceValue, $level);
        $end = $partition::createFromSourceValue($upperBoundSourceValue, $level);

        return new PartitionRange($current, $end);
    }

    public static function isEqual(Partition $a, Partition $b): bool
    {
        return $a->getLevel() === $b->getLevel()
            && $a->getKey() === $b->getKey();
    }

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
