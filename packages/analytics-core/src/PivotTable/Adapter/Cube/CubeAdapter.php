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

namespace Rekalogika\Analytics\PivotTable\Adapter\Cube;

use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\PivotTable\Adapter\Cube\Internal\CubeCellAdapter;
use Rekalogika\Analytics\PivotTable\Util\PropertyMap;
use Rekalogika\PivotTable\Contracts\Cube\Cube as PivotTableCube;
use Rekalogika\PivotTable\Contracts\Cube\CubeCell as PivotTableCubeCell;

final readonly class CubeAdapter implements PivotTableCube
{
    public static function adapt(CubeCell $cubeCell): self
    {
        $propertyMap = new PropertyMap();

        $cubeCell = new CubeCellAdapter(
            cubeCell: $cubeCell,
            propertyMap: $propertyMap,
        );

        return new self(
            apexCell: $cubeCell,
        );
    }


    private function __construct(
        private PivotTableCubeCell $apexCell,
    ) {}

    #[\Override]
    public function getApexCell(): PivotTableCubeCell
    {
        return $this->apexCell;
    }

    #[\Override]
    public function getSubtotalDescription(string $dimensionName): mixed
    {
        return new TranslatableMessage('Total');
    }
}
