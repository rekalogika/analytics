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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionCollection;

final readonly class TreeContext
{
    private NewDefaultTreeNodeFactory $treeNodeFactory;

    public function __construct(
        private RowCollection $rowCollection,
        private DimensionCollection $dimensionCollection,
        int $nodesLimit,
    ) {
        $this->treeNodeFactory = new NewDefaultTreeNodeFactory(
            nodesLimit: $nodesLimit,
            context: $this,
        );
    }

    public function getTreeNodeFactory(): NewDefaultTreeNodeFactory
    {
        return $this->treeNodeFactory;
    }

    public function getRowCollection(): RowCollection
    {
        return $this->rowCollection;
    }

    public function getDimensionCollection(): DimensionCollection
    {
        return $this->dimensionCollection;
    }
}
