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

use Rekalogika\PivotTable\Contracts\Tree\BranchNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class RootBlock extends BranchBlock
{
    protected function __construct(
        BranchNode $treeNode,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, 0, $context);
    }

    #[\Override]
    protected function getHeaderRows(): DefaultRows
    {
        return $this
            ->getChildrenBlockGroup()
            ->getHeaderRows();
    }

    #[\Override]
    protected function getDataRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getDataRows();
    }

    #[\Override]
    protected function getSubtotalRows(array $leafNodes): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getSubtotalRows($leafNodes);
    }
}
