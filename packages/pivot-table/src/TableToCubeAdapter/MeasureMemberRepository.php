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

namespace Rekalogika\PivotTable\TableToCubeAdapter;

use Rekalogika\PivotTable\Contracts\Table\Table;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterMeasureMember;

final class MeasureMemberRepository
{
    /**
     * @var array<string,TableToCubeAdapterMeasureMember> $measureMembers
     */
    private array $measureMembers = [];

    public function __construct(
        private readonly Table $table,
    ) {}

    public function getMeasureMember(string $measureName): TableToCubeAdapterMeasureMember
    {
        return $this->measureMembers[$measureName] ??= new TableToCubeAdapterMeasureMember(
            measureName: $measureName,
            legend: $this->table->getLegend($measureName),
        );
    }
}
