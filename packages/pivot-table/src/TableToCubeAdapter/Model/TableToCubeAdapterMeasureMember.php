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

namespace Rekalogika\PivotTable\TableToCubeAdapter\Model;

use Rekalogika\PivotTable\Contracts\Cube\MeasureMember;

final readonly class TableToCubeAdapterMeasureMember implements MeasureMember
{
    public function __construct(
        private string $measureName,
        private mixed $legend,
    ) {}

    #[\Override]
    public function getMeasureName(): string
    {
        return $this->measureName;
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->legend;
    }
}
