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

use Rekalogika\Analytics\PivotTable\Model\Property;

abstract readonly class TableProperty implements Property
{
    /**
     * @param string|null $measureName The measure this property belongs to,
     * or null if it belongs to a dimension.
     */
    final public function __construct(
        private mixed $value,
        private ?string $measureName = null,
    ) {}

    #[\Override]
    public function getContent(): mixed
    {
        return $this->value;
    }

    public function getMeasureName(): ?string
    {
        return $this->measureName;
    }
}
