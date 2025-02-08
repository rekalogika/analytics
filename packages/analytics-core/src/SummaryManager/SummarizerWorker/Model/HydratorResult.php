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
class HydratorResult
{
    /**
     * @param array<string,mixed> $rawValues
     */
    public function __construct(
        private readonly object $object,
        private readonly array $rawValues,
        private readonly string $groupings,
    ) {}

    public function getObject(): object
    {
        return $this->object;
    }

    /**
     * @return array<string,mixed>
     */
    public function getRawValues(): array
    {
        return $this->rawValues;
    }

    public function getRawValue(string $key): mixed
    {
        return $this->rawValues[$key] ?? null;
    }

    public function isSubtotal(): bool
    {
        return substr_count($this->groupings, '1') !== 0;
    }
}
