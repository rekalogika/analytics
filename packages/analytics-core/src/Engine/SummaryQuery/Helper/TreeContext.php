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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Helper;

use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\DimensionCollection;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\NullMeasureCollection;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultTable;

final readonly class TreeContext
{
    private DefaultTreeNodeFactory $treeNodeFactory;

    public function __construct(
        private ResultContext $resultContext,
        private DefaultTable $table,
        int $nodesLimit,
    ) {
        $this->treeNodeFactory = new DefaultTreeNodeFactory(
            nodesLimit: $nodesLimit,
            context: $this,
        );
    }

    public function getTreeNodeFactory(): DefaultTreeNodeFactory
    {
        return $this->treeNodeFactory;
    }

    public function getTable(): DefaultTable
    {
        return $this->table;
    }

    public function getDimensionCollection(): DimensionCollection
    {
        return $this->resultContext->getDimensionCollection();
    }

    public function getNullMeasureCollection(): NullMeasureCollection
    {
        return $this->resultContext->getNullMeasureCollection();
    }

    public function getResultContext(): ResultContext
    {
        return $this->resultContext;
    }
}
