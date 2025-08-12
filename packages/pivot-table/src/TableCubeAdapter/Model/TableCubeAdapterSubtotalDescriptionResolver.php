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

namespace Rekalogika\PivotTable\TableCubeAdapter\Model;

use Rekalogika\PivotTable\Contracts\Cube\SubtotalDescriptionResolver;
use Rekalogika\PivotTable\Contracts\Table;

final readonly class TableCubeAdapterSubtotalDescriptionResolver implements SubtotalDescriptionResolver
{
    public function __construct(private Table $table) {}

    #[\Override]
    public function getSubtotalDescription(string $dimensionName): mixed
    {
        return $this->table->getSubtotalLegend($dimensionName);
    }
}
