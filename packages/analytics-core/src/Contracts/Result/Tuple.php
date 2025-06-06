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

/**
 * A tuple of dimensions
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends \Traversable<string,Dimension>
 */
interface Tuple extends \Traversable, \Countable
{
    /**
     * @return class-string
     */
    public function getSummaryClass(): string;

    public function get(string $key): ?Dimension;

    public function getByIndex(int $index): Dimension;

    public function first(): ?Dimension;

    public function has(string $key): bool;

    /**
     * @return array<string,mixed>
     */
    public function getMembers(): array;

    public function isSame(self $other): bool;
}
