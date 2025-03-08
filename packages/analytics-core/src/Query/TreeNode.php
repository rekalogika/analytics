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

/**
 * Represent a node in the query result.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends \Traversable<mixed,TreeNode>
 */
interface TreeNode extends \Traversable, \Countable
{
    /**
     * Dimension property name (e.g. country, time.hour)
     */
    public function getKey(): string;

    /**
     * Description of the dimension (e.g. Country, Hour of the day)
     */
    public function getLegend(): string|TranslatableInterface;

    /**
     * The member that this node represents. (e.g. France, 12:00).
     */
    public function getMember(): mixed;

    /**
     * The canonical value. If not in leaf node, the value is null. Usually a
     * number, but can also be an object that represents the value, e.g. Money
     */
    public function getValue(): mixed;

    /**
     * The raw value. If not in leaf node, the value is null.
     */
    public function getRawValue(): int|float|null;

    public function getMeasurePropertyName(): ?string;

    /**
     * Whether this node is a leaf node.
     */
    public function isLeaf(): bool;

    public function traverse(mixed ...$members): ?TreeNode;
}
