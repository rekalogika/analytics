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

namespace Rekalogika\PivotTable\TableCubeAdapter;

use Rekalogika\PivotTable\Contracts\Table;
use Rekalogika\PivotTable\TableCubeAdapter\Model\TableCubeAdapterMeasureMember;

final class MeasureMemberRepository
{
    /**
     * @var array<string,TableCubeAdapterMeasureMember> $measureMembers
     */
    private array $measureMembers = [];

    public function __construct(
        private readonly Table $table,
    ) {}

    public function getMeasureMember(string $measureName): TableCubeAdapterMeasureMember
    {
        return $this->measureMembers[$measureName] ??= new TableCubeAdapterMeasureMember(
            measureName: $measureName,
            legend: $this->table->getLegend($measureName),
        );
    }
}
