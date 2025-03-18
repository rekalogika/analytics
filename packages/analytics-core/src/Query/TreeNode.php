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
    public function getLabel(): string|TranslatableInterface;

    /**
     * The member of the dimension that this node represents. (e.g. France,
     * 12:00).
     */
    public function getMember(): mixed;

    /**
     * The raw member of the dimension as returned by the database.
     */
    public function getRawMember(): mixed;

    /**
     * The member in the form suitable for display. i.e. null values are
     * replaced with the null label.
     */
    public function getDisplayMember(): mixed;

    /**
     * The canonical value. If not in leaf node, the value is null. Usually a
     * number, but can also be an object that represents the value, e.g. Money
     */
    public function getValue(): mixed;

    /**
     * The raw value as returned by the database. If not in leaf node, the value
     * is null.
     */
    public function getRawValue(): mixed;

    /**
     * Like the canonical value, but guaranteed to be in numeric format.
     */
    public function getNumericValue(): int|float;

    /**
     * The unit of the value. If not in leaf node, the value is always null.
     */
    public function getUnit(): ?Unit;

    /**
     * Whether this node is a leaf node.
     */
    public function isLeaf(): bool;

    public function traverse(mixed ...$members): ?TreeNode;
}
