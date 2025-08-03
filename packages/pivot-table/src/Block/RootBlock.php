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

use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class RootBlock extends BranchBlock
{
    protected function __construct(
        TreeNodeDecorator $node,
        BlockContext $context,
    ) {
        parent::__construct(
            node: $node,
            parentNode: null,
            parent: null,
            level: 0,
            context: $context,
        );
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        return $this
            ->getChildrenBlockGroup()
            ->getHeaderRows();
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getDataRows();
    }
}
