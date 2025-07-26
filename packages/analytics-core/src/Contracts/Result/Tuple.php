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

namespace Rekalogika\Analytics\Contracts\Result;

use Doctrine\Common\Collections\Expr\Expression;

/**
 * A tuple of dimensions. A collection of dimensions that identifies a unique
 * intersection of members from different dimensions in the cube. A tuple is
 * ordered, the order of dimensions is significant. The members of a tuple must
 * be from unique dimensions from the same summary class.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends \Traversable<string,Dimension>
 */
interface Tuple extends \Traversable, \Countable
{
    /**
     * The summary class that this tuple belongs to.
     *
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * Gets a dimension by its name.
     */
    public function getByName(string $name): ?Dimension;

    /**
     * Gets a dimension by its index. 0 is the first dimension.
     */
    public function getByIndex(int $index): ?Dimension;

    public function has(string $name): bool;

    /**
     * @return array<string,mixed>
     */
    public function getMembers(): array;

    public function isSame(self $other): bool;

    public function getCondition(): ?Expression;
}
