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

namespace Rekalogika\PivotTable\Block;

use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class RootBlock extends NodeBlock
{
    protected function __construct(
        TreeNode $treeNode,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, 0, $context);
    }

    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        return $this
            ->createGroupBlock($this->getBranchNode(), $this->getLevel())
            ->getHeaderRows();
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        return $this
            ->createGroupBlock($this->getBranchNode(), $this->getLevel())
            ->getDataRows();
        ;
    }
}
