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

use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultUnpivotRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTreeNode;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultUnit;

final class UnpivotTableToTreeTransformer
{
    /**
     * @param iterable<ResultUnpivotRow> $rows
     * @param 'tree'|'table' $type
     * @return list<DefaultTreeNode>
     */
    public static function transform(
        iterable $rows,
        string $type,
    ): array {
        $transformer = new self();

        return match ($type) {
            'tree' => $transformer->transformToTree($rows),
            'table' => $transformer->transformToTable($rows),
        };
    }

    /**
     * @var list<DefaultTreeNode>
     */
    private array $currentPath = [];

    /**
     * @var list<DefaultTreeNode>
     */
    private array $tree = [];

    private function addDimension(
        ResultValue $resultValue,
        int $columnNumber,
        bool $forceCreate,
    ): void {
        $node = DefaultTreeNode::createBranchNode(
            key: $resultValue->getField(),
            label: $resultValue->getLabel(),
            member: $resultValue->getValue(),
        );

        $current = $this->currentPath[$columnNumber] ?? null;

        if ($current !== null && $current->isEqual($node) && !$forceCreate) {
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
        /** @psalm-suppress MixedAssignment */
        $rawValue = $resultValue->getRawValue();

        if (!\is_int($rawValue) && !\is_float($rawValue)) {
            $rawValue = 0;
        }

        $node = DefaultTreeNode::createLeafNode(
            key: $lastResultValue->getField(),
            label: $lastResultValue->getLabel(),
            member: $lastResultValue->getValue(),
            value: $resultValue->getValue(),
            rawValue: $rawValue,
            numericValue: $resultValue->getNumericValue(),
            unit: DefaultUnit::createFromResultValue($resultValue),
        );

        if ($columnNumber === 0) {
            $this->currentPath = [$node];
            $this->tree[] = $node;

            return;
        }

        $parent = end($this->currentPath);

        if ($parent instanceof DefaultTreeNode) {
            $parent->addChild($node);
        } else {
            $this->currentPath = [$node];
            $this->tree[] = $node;
        }
    }

    /**
     * @param iterable<ResultUnpivotRow> $rows
     * @return list<DefaultTreeNode>
     */
    private function transformToTree(iterable $rows): array
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

                $this->addDimension($resultValue, $columnNumber, false);

                $columnNumber++;
            }
        }

        return $this->tree;
    }

    /**
     * @param iterable<ResultUnpivotRow> $rows
     * @return list<DefaultTreeNode>
     */
    private function transformToTable(iterable $rows): array
    {
        $this->currentPath = [];
        $this->tree = [];
        $previousRow = null;

        foreach ($rows as $row) {
            $dimensions = $row->getDimensions();
            $columnNumber = 0;
            $sameAsPrevious = $previousRow !== null && $previousRow->hasSameTuple($row);

            foreach ($dimensions as $resultValue) {
                // if last dimension
                if ($columnNumber === \count($dimensions) - 1) {
                    $this->addMeasure($resultValue, $row->getMeasure(), $columnNumber);

                    break;
                }

                if (!$sameAsPrevious) {
                    $this->addDimension($resultValue, $columnNumber, true);
                }

                $columnNumber++;
            }

            $previousRow = $row;
        }

        return $this->tree;
    }
}
