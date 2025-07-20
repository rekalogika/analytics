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

final class EmptyBlock extends BranchBlock
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        return new DefaultRows([], $this);
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        return new DefaultRows([], $this);
    }

    #[\Override]
    protected function createSubtotalRows(iterable $leafNodes): DefaultRows
    {
        return new DefaultRows([], $this);
    }
}
