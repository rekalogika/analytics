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

namespace Rekalogika\Analytics\Contracts\Model;

/**
 * @template-covariant T
 */
interface Partition extends \Stringable
{
    /**
     * Returns all the levels of partitioning used by this partitioning
     * scheme.
     *
     * @return non-empty-list<int>
     */
    public static function getAllLevels(): array;

    /**
     * Returns the level of this partition. It must be one of the levels
     * returned by `getAllLevels()`.
     */
    public function getLevel(): int;

    /**
     * Returns the key of this partition. A partition is identified by its
     * key and level. The key is usually the first value of in the partition,
     * but not neccessarily.
     */
    public function getKey(): int;

    /**
     * Creates a partition from the source value and level.'
     */
    public static function createFromSourceValue(
        mixed $source,
        int $level,
    ): static;

    /**
     * The lowest value of the source data in the partition, inclusive. This
     * must be the same as the upper bound of the previous neighboring
     * partition.
     * 
     * @return T
     */
    public function getLowerBound(): mixed;

    /**
     * The highest value of the source data in the partition, exclusive. This
     * must be the same as the lower bound of the next neighboring partition.
     * 
     * @return T
     */
    public function getUpperBound(): mixed;

    /**
     * Returns the higher partition that contains this partition.
     */
    public function getContaining(): ?static;

    /**
     * Returns the next neighboring partition on the same level as this
     * partition.
     */
    public function getNext(): ?static;

    /**
     * Returns the previous neighboring partition on the same level as this
     * partition.
     */
    public function getPrevious(): ?static;
}
