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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker;

use Rekalogika\Analytics\Query\SummaryField;
use Rekalogika\Analytics\Query\SummaryItem;
use Rekalogika\Analytics\Query\SummaryLeafItem;

final class ArrayToTreeTransformer
{
    /**
     * @var list<SummaryItem|SummaryLeafItem>
     */
    private array $currentPath = [];

    /**
     * @var list<SummaryItem|SummaryLeafItem>
     */
    private array $tree = [];

    private function addDimension(SummaryItem $item, int $columnNumber): void
    {
        $item = clone $item;

        $current = $this->currentPath[$columnNumber] ?? null;

        if ($current !== null && $current->isEqual($item)) {
            return;
        }

        if ($columnNumber === 0) {
            $this->currentPath = [$item];
            $this->tree[] = $item;

            return;
        }

        $parent = $this->currentPath[$columnNumber - 1];

        $currentPath = \array_slice($this->currentPath, 0, $columnNumber);
        $currentPath[$columnNumber] = $item;

        $this->currentPath = array_values($currentPath);

        if ($parent instanceof SummaryItem) {
            $parent->addChild($item);
        }
    }

    private function addMeasure(SummaryLeafItem $item): void
    {
        $parent = end($this->currentPath);

        if ($parent instanceof SummaryItem) {
            $parent->addChild($item);
        } else {
            $this->currentPath = [$item];
            $this->tree[] = $item;
        }
    }

    /**
     * @param iterable<list<SummaryField>> $inputArray
     * @return list<SummaryItem|SummaryLeafItem>
     */
    public function arrayToTree(iterable $inputArray): array
    {
        $this->currentPath = [];
        $this->tree = [];

        foreach ($inputArray as $row) {
            foreach ($row as $columnNumber => $item) {
                if ($item instanceof SummaryItem) {
                    $this->addDimension($item, $columnNumber);
                } elseif ($item instanceof SummaryLeafItem) {
                    $this->addMeasure($item);
                } else {
                    throw new \UnexpectedValueException('Item must be a dimension or a measure');
                }
            }
        }

        return $this->tree;
    }
}
