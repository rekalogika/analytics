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
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultUnpivotRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;

final class UnpivotTableToTreeTransformer
{
    /**
     * @var list<DefaultSummaryNode>
     */
    private array $currentPath = [];

    /**
     * @var list<DefaultSummaryNode>
     */
    private array $tree = [];

    private function addDimension(ResultValue $resultValue, int $columnNumber): void
    {
        $node = DefaultSummaryNode::createBranchNode(
            key: $resultValue->getField(),
            legend: $resultValue->getLabel(),
            member: $resultValue->getValue(),
        );

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

        $parent->addChild($node);
    }

    private function addMeasure(
        ResultValue $lastResultValue,
        ResultValue $resultValue,
        int $columnNumber,
    ): void {
        $rawValue = $resultValue->getRawValue();

        if (!\is_int($rawValue) && !\is_float($rawValue)) {
            throw new \UnexpectedValueException('Value must be an integer or float');
        }

        $node = DefaultSummaryNode::createLeafNode(
            key: $lastResultValue->getField(),
            legend: $lastResultValue->getLabel(),
            member: $lastResultValue->getValue(),
            value: $resultValue->getValue(),
            rawValue: $rawValue,
        );

        if ($columnNumber === 0) {
            $this->currentPath = [$node];
            $this->tree[] = $node;

            return;
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
     * @param iterable<ResultUnpivotRow> $rows
     * @return list<DefaultSummaryNode>
     */
    public function transform(iterable $rows): array
    {
        $this->currentPath = [];
        $this->tree = [];

        foreach ($rows as $row) {
            $dimensions = $row->getDimensions();
            $columnNumber = 0;

            foreach ($dimensions as $resultValue) {
                // if last dimension
                if ($columnNumber === \count($dimensions) - 1) {
                    $this->addMeasure($resultValue, $row->getMeasure(), $columnNumber);

                    break;
                }

                $this->addDimension($resultValue, $columnNumber);

                $columnNumber++;
            }
        }

        return $this->tree;
    }
}
