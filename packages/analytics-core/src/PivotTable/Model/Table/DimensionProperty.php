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

namespace Rekalogika\Analytics\PivotTable\Model\Table;

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\PivotTable\Model\Property;

abstract readonly class DimensionProperty implements Property
{
    final public function __construct(private Dimension $dimension) {}

    #[\Override]
    abstract public function getContent(): mixed;

    final public function getDimension(): Dimension
    {
        return $this->dimension;
    }
}
