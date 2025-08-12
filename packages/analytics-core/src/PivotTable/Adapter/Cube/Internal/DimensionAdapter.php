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

namespace Rekalogika\Analytics\PivotTable\Adapter\Cube\Internal;

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\PivotTable\Util\PropertyMap;
use Rekalogika\PivotTable\Contracts\Cube\Dimension as PivotTableDimension;

final readonly class DimensionAdapter implements PivotTableDimension
{
    public function __construct(
        private Dimension $dimension,
        private PropertyMap $propertyMap,
    ) {}

    #[\Override]
    public function getName(): string
    {
        return $this->dimension->getName();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->propertyMap->getDimensionLabel($this->dimension);
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->propertyMap->getDimensionMember($this->dimension);
    }
}
