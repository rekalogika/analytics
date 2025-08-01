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
    public function getHeaderRows(): DefaultRows
    {
        return new DefaultRows([], $this->getElementContext());
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        return new DefaultRows([], $this->getElementContext());
    }
}
