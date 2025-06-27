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

/**
 * @extends NodeBlock<TreeNode>
 */
final class EmptyBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        return new DefaultRows([]);
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        return new DefaultRows([]);
    }
}
