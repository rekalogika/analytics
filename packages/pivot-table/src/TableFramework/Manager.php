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

namespace Rekalogika\PivotTable\TableFramework;

use Rekalogika\PivotTable\Contracts\Table;
use Rekalogika\PivotTable\TableFramework\Implementation\DefaultIdentityStrategy;
use Rekalogika\PivotTable\TableFramework\Implementation\DefaultTreeNode;

final class Manager
{
    private RowRepository $rowRepository;
    private DimensionRepository $dimensionRepository;

    public function __construct(
        private readonly Table $table,
        ?IdentityStrategy $identityStrategy = null,
    ) {
        $identityStrategy ??= new DefaultIdentityStrategy();

        $this->rowRepository = new RowRepository($table, $identityStrategy);
        $this->dimensionRepository = $this->rowRepository
            ->getDimensionRepository();
    }

    public function getRowRepository(): RowRepository
    {
        return $this->rowRepository;
    }

    public function getDimensionRepository(): DimensionRepository
    {
        return $this->dimensionRepository;
    }

    public function getLegend(string $dimension): mixed
    {
        return $this->table->getLegend($dimension);
    }

    /**
     * @param list<string> $path
     * @return DefaultTreeNode
     */
    public function createTree(array $path): DefaultTreeNode
    {
        return DefaultTreeNode::createRoot(
            manager: $this,
            path: $path,
        );
    }
}
