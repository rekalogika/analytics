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

namespace Rekalogika\Analytics\PivotTable\Block;

use Rekalogika\Analytics\PivotTable\Table\Rows;
use Rekalogika\Analytics\PivotTable\TreeNode;

final class RootBlock extends NodeBlock
{
    protected function __construct(
        TreeNode $treeNode,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, 0, $context);
    }

    #[\Override]
    protected function createHeaderRows(): Rows
    {
        return $this
            ->createGroupBlock($this->getBranchNode(), $this->getLevel())
            ->getHeaderRows();
    }

    #[\Override]
    protected function createDataRows(): Rows
    {
        return $this
            ->createGroupBlock($this->getBranchNode(), $this->getLevel())
            ->getDataRows();
        ;
    }
}
