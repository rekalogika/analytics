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
use Rekalogika\PivotTable\TableToCubeAdapter\Helper\TableToCubeAdapterManager;
use Rekalogika\PivotTable\TableToCubeAdapter\Implementation\DefaultIdentityStrategy;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterCube;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterSubtotalDescriptionResolver;

final readonly class TableToCubeAdapter
{
    private TableToCubeAdapterManager $manager;
    private TableToCubeAdapterSubtotalDescriptionResolver $subtotalDescriptionResolver;

    public function __construct(
        private Table $table,
        private IdentityStrategy $identityStrategy = new DefaultIdentityStrategy(),
    ) {
        $this->manager = new TableToCubeAdapterManager(
            table: $this->table,
            identityStrategy: $this->identityStrategy,
        );

        $this->subtotalDescriptionResolver = new TableToCubeAdapterSubtotalDescriptionResolver(
            table: $this->table,
        );
    }

    public function getApexCube(): TableToCubeAdapterCube
    {
        return $this->manager->getApexCube();
    }

    public function getSubtotalDescriptionResolver(): TableToCubeAdapterSubtotalDescriptionResolver
    {
        return $this->subtotalDescriptionResolver;
    }
}
