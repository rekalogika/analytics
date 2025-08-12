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

use Rekalogika\PivotTable\Block\Model\CubeCellDecorator;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

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

        // add a header and data column for each of the child blocks
        foreach ($this->getChildBlocks() as $childBlock) {
            $childHeaderRows = $childBlock->getHeaderRows();
            $headerRows = $headerRows->appendRight($childHeaderRows);
        }

        // add a legend if the dimension is not marked as skipped
        $child = $this->getOneChildCube();

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
        $nextKey = $this->getContext()->getNextKey();

        if ($nextKey === null) {
            throw new \RuntimeException('Next key is not set in the context.');
        }

        $dataRows = new DefaultRows([], $context);

        foreach ($this->getChildBlocks() as $childBlock) {
            $childDataRows = $childBlock->getDataRows();
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        return $dataRows;
    }

    /**
     * @return non-empty-list<CubeCellDecorator>
     */
    #[\Override]
    protected function createPrototypeCubes(): array
    {
        $firstPivoted = $this->getContext()->getFirstPivotedKey();
        $currentKeys = array_keys($this->getCube()->getTuple());
        $existsInTuple = \in_array($firstPivoted, $currentKeys, true);

        if ($firstPivoted === null || !$existsInTuple) {
            $result = $this->getContext()
                ->getApexCubeCell()
                ->drillDownWithoutBalancing($this->getChildKey());
        } else {
            $result = $this->getCube()
                ->rollUpAllExcept([$firstPivoted])
                ->drillDownWithoutBalancing($this->getChildKey());
        }

        $result = array_values(iterator_to_array($result));

        if ($result === []) {
            throw new \RuntimeException(\sprintf(
                'No prototype nodes found for child key "%s".',
                $this->getChildKey(),
            ));
        }

        return $result;
    }
}
