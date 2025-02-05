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

namespace Rekalogika\Analytics\Query;

class SummaryLeafItem extends SummaryField
{
    public function __construct(
        string $key,
        private readonly mixed $value,
        private readonly int|float|null $rawValue,
        mixed $legend,
        mixed $item,
    ) {
        parent::__construct(
            key: $key,
            legend: $legend,
            item: $item,
        );
    }

    public function isLeaf(): bool
    {
        return true;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function getRawValue(): int|float|null
    {
        return $this->rawValue;
    }

    public function getChildren(): array
    {
        return [];
    }
}
