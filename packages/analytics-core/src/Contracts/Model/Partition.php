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

interface Partition extends \Stringable
{
    /**
     * @return non-empty-list<int>
     */
    public static function getAllLevels(): array;

    public function getLevel(): int;

    public function getKey(): int|string;

    public static function createFromSourceValue(
        mixed $source,
        int $level,
    ): self;

    /**
     * The lowest value of the source data in the partition, inclusive. This
     * must be the same as the upper bound of the previous neighboring
     * partition.
     */
    public function getLowerBound(): int|string;

    /**
     * The highest value of the source data in the partition, exclusive. This
     * must be the same as the lower bound of the next neighboring partition.
     */
    public function getUpperBound(): int|string;

    public function getContaining(): ?static;

    public function getNext(): ?static;

    public function getPrevious(): ?static;
}
