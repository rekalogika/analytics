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

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Represents a query result.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Result
{
    public function getLabel(): TranslatableInterface;

    /**
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * The dimension names of this result. It is the same as the groupBy clause
     * of the query.
     *
     * @return list<string>
     */
    public function getDimensionality(): array;

    public function getTree(): TreeNode;

    // public function getNormalTable(): NormalTable;

    public function getTable(): Table;

    /**
     * Gets the root cube cell.
     */
    public function getCube(): CubeCell;

    /**
     * @return iterable<CubeCell>
     */
    public function getAllCubes(): iterable;
}
