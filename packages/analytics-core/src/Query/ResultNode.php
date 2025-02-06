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

use Symfony\Contracts\Translation\TranslatableInterface;

interface ResultNode
{
    /**
     * Dimension or measure property name (e.g. country, time.hour, revenue)
     */
    public function getKey(): string;

    /**
     * Description of the dimension or measure (e.g. Country, Hour of the day,
     * Revenue)
     */
    public function getLegend(): string|TranslatableInterface;

    /**
     * The item that this node represents. (e.g. France, 12:00).
     */
    public function getItem(): mixed;

    /**
     * The children of this node.
     *
     * @return iterable<ResultNode>
     */
    public function getChildren(): iterable;

    public function getChild(mixed $item): ?ResultNode;

    /**
     * The canonical value. If not in leaf node, the value is null. Usually a
     * number, but can also be an object that represents the value, e.g. Money
     */
    public function getValue(): object|int|float|null;

    /**
     * The raw value. If not in leaf node, the value is null.
     */
    public function getRawValue(): int|float|null;

    public function getMeasurePropertyName(): ?string;

    /**
     * Whether this node is a leaf node.
     */
    public function isLeaf(): bool;

    public function getPath(mixed ...$items): ?ResultNode;
}
