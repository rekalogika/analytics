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
use Rekalogika\PivotTable\TableCubeAdapter\Model\TableCubeAdapterDimension;

final class DimensionRepository
{
    /**
     * @var array<string,array<string,TableCubeAdapterDimension>> $dimensions
     */
    private array $dimensions = [];

    public function __construct(
        private readonly Table $table,
        private readonly IdentityStrategy $identityStrategy,
    ) {}

    public function getDimension(
        string $dimensionName,
        mixed $dimensionMember,
    ): TableCubeAdapterDimension {
        $signature = $this->identityStrategy
            ->getMemberSignature($dimensionMember);

        return $this->dimensions[$dimensionName][$signature] ??= new TableCubeAdapterDimension(
            name: $dimensionName,
            legend: $this->table->getLegend($dimensionName),
            member: $dimensionMember,
        );
    }
}
