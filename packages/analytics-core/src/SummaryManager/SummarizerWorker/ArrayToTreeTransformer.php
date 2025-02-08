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

    private function addDimension(DefaultSummaryNode $node, int $columnNumber): void
    {
        $node = clone $node;

        $current = $this->currentPath[$columnNumber] ?? null;

        if ($current !== null && $current->isEqual($node)) {
            return;
        }

        if ($columnNumber === 0) {
            $this->currentPath = [$node];
            $this->tree[] = $node;

            return;
        }

        $parent = $this->currentPath[$columnNumber - 1];

        $currentPath = \array_slice($this->currentPath, 0, $columnNumber);
        $currentPath[$columnNumber] = $node;

        $this->currentPath = array_values($currentPath);

        if (!$parent->isLeaf()) {
            $parent->addChild($node);
        }
    }

    private function addMeasure(DefaultSummaryNode $node): void
    {
        if (!$node->isLeaf()) {
            throw new \UnexpectedValueException('Node must be a leaf');
        }

        $parent = end($this->currentPath);

        if ($parent instanceof DefaultSummaryNode) {
            $parent->addChild($node);
        } else {
            $this->currentPath = [$node];
            $this->tree[] = $node;
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
            foreach ($row as $columnNumber => $node) {
                if ($node->isLeaf()) {
                    $this->addMeasure($node);
                } else {
                    $this->addDimension($node, $columnNumber);
                }
            }
        }

        return $this->tree;
    }
}
