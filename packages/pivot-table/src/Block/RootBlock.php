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

use Rekalogika\PivotTable\Implementation\Table\DefaultRows;
use Rekalogika\PivotTable\TableFramework\Cube;

final class RootBlock extends BranchBlock
{
    protected function __construct(
        Cube $cube,
        BlockContext $context,
    ) {
        parent::__construct(
            cube: $cube,
            parent: null,
            context: $context,
        );
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getHeaderRows();
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getDataRows();
    }
}
