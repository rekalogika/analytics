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

interface SummaryNode
{
    public function getLegend(): mixed;

    public function getItem(): mixed;

    public function getKey(): string;

    /**
     * @return list<SummaryNode>
     */
    public function getChildren(): array;

    public function getValue(): mixed;

    public function getRawValue(): int|float|null;

    public function isLeaf(): bool;
}
