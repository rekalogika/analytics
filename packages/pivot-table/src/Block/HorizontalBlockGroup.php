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

use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;
use Rekalogika\PivotTable\TableFramework\Cube;

final class HorizontalBlockGroup extends BlockGroup
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        $context = $this->getElementContext();
        $nextKey = $this->getContext()->getNextKey();

        if ($nextKey === null) {
            throw new \RuntimeException('Next key is not set in the context.');
        }

        $headerRows = new DefaultRows([], $context);
        $prototypeCubes = $this->getPrototypeNodes();

        // add a header and data column for each of the child blocks
        foreach ($this->getChildBlocks($prototypeCubes) as $childBlock) {
            $childHeaderRows = $childBlock->getHeaderRows();
            $headerRows = $headerRows->appendRight($childHeaderRows);
        }

        // add a legend if the dimension is not marked as skipped
        $child = $this->getOneChildCube($prototypeCubes);

        if (!$this->getContext()->isLegendSkipped($nextKey)) {
            $nameCell = new DefaultHeaderCell(
                name: $nextKey,
                content: $child->getLegend($nextKey),
                context: $context,
            );

            $headerRows = $nameCell->appendRowsBelow($headerRows);
        }

        return $headerRows;
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        $context = $this->getElementContext();
        $dataRows = new DefaultRows([], $context);
        $prototypeCubes = $this->getPrototypeNodes();

        foreach ($this->getChildBlocks($prototypeCubes) as $childBlock) {
            $childDataRows = $childBlock->getDataRows();
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        return $dataRows;
    }

    /**
     * @return non-empty-list<Cube>
     */
    private function getPrototypeNodes(): array
    {
        $result = $this->getContext()
            ->getApexCube()
            ->drillDown($this->getChildKey());

        if ($result === []) {
            throw new \RuntimeException(\sprintf(
                'No prototype nodes found for child key "%s".',
                $this->getChildKey(),
            ));
        }

        return $result;
    }
}
