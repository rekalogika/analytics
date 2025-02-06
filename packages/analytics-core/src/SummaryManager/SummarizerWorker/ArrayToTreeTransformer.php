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

use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\DefaultSummaryNode;

final class ArrayToTreeTransformer
{
    /**
     * @var list<DefaultSummaryNode>
     */
    private array $currentPath = [];

    /**
     * @var list<DefaultSummaryNode>
     */
    private array $tree = [];

    private function addDimension(DefaultSummaryNode $item, int $columnNumber): void
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

        if (!$parent->isLeaf()) {
            $parent->addChild($item);
        }
    }

    private function addMeasure(DefaultSummaryNode $item): void
    {
        if (!$item->isLeaf()) {
            throw new \UnexpectedValueException('Item must be a leaf');
        }

        $parent = end($this->currentPath);

        if ($parent instanceof DefaultSummaryNode) {
            $parent->addChild($item);
        } else {
            $this->currentPath = [$item];
            $this->tree[] = $item;
        }
    }

    /**
     * @param iterable<list<DefaultSummaryNode>> $inputArray
     * @return list<DefaultSummaryNode>
     */
    public function arrayToTree(iterable $inputArray): array
    {
        $this->currentPath = [];
        $this->tree = [];

        foreach ($inputArray as $row) {
            foreach ($row as $columnNumber => $item) {
                if ($item->isLeaf()) {
                    $this->addMeasure($item);
                } else {
                    $this->addDimension($item, $columnNumber);
                }
            }
        }

        return $this->tree;
    }
}
