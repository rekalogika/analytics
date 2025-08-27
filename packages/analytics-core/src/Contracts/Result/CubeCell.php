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
 * Represent a cell in a cube. A cell has coordinates and one or more measures
 * associated with it.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface CubeCell extends Cell
{
    /**
     * The input summary class for this cube.
     *
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * The apex CubeCell, or the CubeCell without any dimensions.
     */
    public function getApex(): self;

    /**
     * Determine if this TreeNode was created for interpolation, or to balance
     * the tree, and does not result from the query.
     */
    public function isNull(): bool;

    public function rollUp(string $dimension): self;

    public function drillDown(string $dimension): CubeCells;

    public function slice(string $dimension, mixed $member): ?self;

    public function find(string $dimension, mixed $argument): ?self;

    public function dice(?Expression $predicate): self;
}
