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
use Rekalogika\Analytics\Contracts\Collection\OrderedMap;

/**
 * Coordinates of dimensions. A collection of dimensions that identifies a
 * unique intersection of members from different dimensions in the cube. The
 * dimensions are in no particular order. The members must be from unique
 * dimensions from the same summary class.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends OrderedMap<string,Dimension>
 */
interface Coordinates extends OrderedMap
{
    /**
     * The summary class that this coordinates belongs to.
     *
     * @return class-string
     */
    public function getSummaryClass(): string;

    public function getPredicate(): ?Expression;

    /**
     * @return list<string>
     */
    public function getDimensionality(): array;
}
