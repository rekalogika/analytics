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

namespace Rekalogika\PivotTable\ArrayTable;

use Rekalogika\PivotTable\Contracts\Row;

final class ArrayRow implements Row
{
    /**
     * @param array<string,mixed> $dimensions
     * @param array<string,mixed> $measures
     */
    public function __construct(
        private readonly array $dimensions,
        private readonly array $measures,
    ) {}

    #[\Override]
    public function getDimensions(): iterable
    {
        return $this->dimensions;
    }

    #[\Override]
    public function getMeasures(): iterable
    {
        return $this->measures;
    }
}
