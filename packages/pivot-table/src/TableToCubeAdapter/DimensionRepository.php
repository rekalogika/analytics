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

use Rekalogika\PivotTable\Contracts\Table;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterDimension;

final class DimensionRepository
{
    /**
     * @var array<string,array<string,TableToCubeAdapterDimension>> $dimensions
     */
    private array $dimensions = [];

    public function __construct(
        private readonly Table $table,
        private readonly IdentityStrategy $identityStrategy,
    ) {}

    public function getDimension(
        string $dimensionName,
        mixed $dimensionMember,
    ): TableToCubeAdapterDimension {
        $signature = $this->identityStrategy
            ->getMemberSignature($dimensionMember);

        return $this->dimensions[$dimensionName][$signature] ??= new TableToCubeAdapterDimension(
            name: $dimensionName,
            legend: $this->table->getLegend($dimensionName),
            member: $dimensionMember,
        );
    }
}
