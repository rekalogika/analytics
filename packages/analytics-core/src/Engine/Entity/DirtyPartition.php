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

namespace Rekalogika\Analytics\Engine\Entity;

use Rekalogika\Analytics\Contracts\Model\Partition;

final readonly class DirtyPartition
{
    private \DateTimeInterface $earliest;
    private \DateTimeInterface $latest;

    /**
     * @param class-string $summaryClass
     * @param class-string<Partition> $partitionClass
     */
    public function __construct(
        private string $summaryClass,
        private int $level,
        private int $key,
        string $earliest,
        string $latest,
        private int $count,
        private string $partitionClass,
    ) {
        $this->earliest = new \DateTimeImmutable($earliest);
        $this->latest = new \DateTimeImmutable($latest);
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getKey(): int
    {
        return $this->key;
    }

    public function getEarliest(): \DateTimeInterface
    {
        return $this->earliest;
    }

    public function getLatest(): \DateTimeInterface
    {
        return $this->latest;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return class-string
     */
    public function getPartitionClass(): string
    {
        return $this->partitionClass;
    }

    public function getPartition(): Partition
    {
        return ($this->partitionClass)::createFromSourceValue(
            source: $this->key,
            level: $this->level,
        );
    }
}
