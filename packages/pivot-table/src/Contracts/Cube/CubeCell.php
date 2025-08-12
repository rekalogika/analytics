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

namespace Rekalogika\PivotTable\Contracts\Cube;

interface CubeCell
{
    /**
     * @return array<string,Dimension>
     */
    public function getTuple(): array;

    public function getValue(): mixed;

    public function isNull(): bool;

    public function slice(string $dimensionName, mixed $member): CubeCell;

    /**
     * @return iterable<CubeCell>
     */
    public function drillDown(string $dimensionName): iterable;

    public function rollUp(string $dimensionName): CubeCell;
}
