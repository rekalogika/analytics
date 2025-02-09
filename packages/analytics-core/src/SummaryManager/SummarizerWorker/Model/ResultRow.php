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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

/**
 * @internal
 */
class ResultRow
{
    /**
     * @param array<string,ResultValue> $dimensions
     * @param array<string,ResultValue> $measures
     */
    public function __construct(
        private readonly object $object,
        private array $dimensions,
        private array $measures,
        private readonly string $groupings,
    ) {}

    public function getMeasure(string $measure): ResultValue
    {
        return $this->measures[$measure]
            ?? throw new \RuntimeException('Measure ' . $measure . ' not found in row');
    }

    public function getDimensionMember(string $dimension): ResultValue
    {
        return $this->dimensions[$dimension]
            ?? throw new \RuntimeException('Dimension ' . $dimension . ' not found in row');
    }

    public function getObject(): object
    {
        return $this->object;
    }

    public function isSubtotal(): bool
    {
        return substr_count($this->groupings, '1') !== 0;
    }
}
