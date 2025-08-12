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

use Rekalogika\PivotTable\Contracts\Cube\Cube;
use Rekalogika\PivotTable\Contracts\Cube\CubeCell;
use Rekalogika\PivotTable\Contracts\Table\Table;
use Rekalogika\PivotTable\TableToCubeAdapter\Helper\TableToCubeAdapterManager;
use Rekalogika\PivotTable\TableToCubeAdapter\Implementation\DefaultIdentityStrategy;

final readonly class TableToCubeAdapter implements Cube
{
    private TableToCubeAdapterManager $manager;

    public function __construct(
        private Table $table,
        private IdentityStrategy $identityStrategy = new DefaultIdentityStrategy(),
    ) {
        $this->manager = new TableToCubeAdapterManager(
            table: $this->table,
            identityStrategy: $this->identityStrategy,
        );
    }

    #[\Override]
    public function getApexCell(): CubeCell
    {
        return $this->manager->getApexCube();
    }

    #[\Override]
    public function getSubtotalDescription(string $dimensionName): mixed
    {
        return $this->table->getSubtotalLegend($dimensionName);
    }
}
