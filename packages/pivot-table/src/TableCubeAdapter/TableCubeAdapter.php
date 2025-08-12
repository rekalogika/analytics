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
use Rekalogika\PivotTable\TableCubeAdapter\Implementation\DefaultIdentityStrategy;
use Rekalogika\PivotTable\TableCubeAdapter\Model\TableCubeAdapterCube;
use Rekalogika\PivotTable\TableCubeAdapter\Model\TableCubeAdapterSubtotalDescriptionResolver;

final readonly class TableCubeAdapter
{
    private TableCubeAdapterManager $manager;
    private TableCubeAdapterSubtotalDescriptionResolver $subtotalDescriptionResolver;

    public function __construct(
        private Table $table,
        private IdentityStrategy $identityStrategy = new DefaultIdentityStrategy(),
    ) {
        $this->manager = new TableCubeAdapterManager(
            table: $this->table,
            identityStrategy: $this->identityStrategy,
        );

        $this->subtotalDescriptionResolver = new TableCubeAdapterSubtotalDescriptionResolver(
            table: $this->table,
        );
    }

    public function getApexCube(): TableCubeAdapterCube
    {
        return $this->manager->getApexCube();
    }

    public function getSubtotalDescriptionResolver(): TableCubeAdapterSubtotalDescriptionResolver
    {
        return $this->subtotalDescriptionResolver;
    }
}
