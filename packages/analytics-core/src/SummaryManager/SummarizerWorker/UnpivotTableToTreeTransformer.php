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

use Rekalogika\Analytics\Query\Dimension;
use Rekalogika\Analytics\Query\Measure;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTreeNode;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTreeResult;

final class UnpivotTableToTreeTransformer
{
    /**
     * @param 'tree'|'table' $type
     */
    public static function transform(
        DefaultNormalTable $normalTable,
        string $type,
    ): DefaultTreeResult {
        $transformer = new self();

        $rootNodes = match ($type) {
            'tree' => $transformer->transformToTree($normalTable),
            'table' => $transformer->transformToTable($normalTable),
        };

        return new DefaultTreeResult(
            summaryClass: $normalTable->getSummaryClass(),
            children: $rootNodes,
        );
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
        Dimension $dimension,
        int $columnNumber,
        bool $forceCreate,
    ): void {
        $node = DefaultTreeNode::createBranchNode(
            summaryClass: $dimension->getSummaryClass(),
            key: $dimension->getKey(),
            label: $dimension->getLabel(),
            member: $dimension->getMember(),
            rawMember: $dimension->getRawMember(),
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
        Dimension $lastDimension,
        Measure $measure,
        int $columnNumber,
    ): void {
        /** @psalm-suppress MixedAssignment */
        $rawValue = $measure->getRawValue();

        if (!\is_int($rawValue) && !\is_float($rawValue)) {
            $rawValue = 0;
        }

        $node = DefaultTreeNode::createLeafNode(
            summaryClass: $lastDimension->getSummaryClass(),
            key: $lastDimension->getKey(),
            label: $lastDimension->getLabel(),
            member: $lastDimension->getMember(),
            rawMember: $lastDimension->getRawMember(),
            value: $measure->getValue(),
            rawValue: $rawValue,
            numericValue: $measure->getNumericValue(),
            unit: $measure->getUnit(),
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
     * @return list<DefaultTreeNode>
     */
    private function transformToTree(DefaultNormalTable $normalTable): array
    {
        $this->currentPath = [];
        $this->tree = [];

        foreach ($normalTable as $row) {
            $dimensions = $row->getTuple();
            $columnNumber = 0;

            foreach ($dimensions as $dimension) {
                // if last dimension
                if ($columnNumber === \count($dimensions) - 1) {
                    $this->addMeasure($dimension, $row->getMeasure(), $columnNumber);

                    break;
                }

                $this->addDimension($dimension, $columnNumber, false);

                $columnNumber++;
            }
        }

        return $this->tree;
    }

    /**
     * @return list<DefaultTreeNode>
     */
    private function transformToTable(DefaultNormalTable $normalTable): array
    {
        $this->currentPath = [];
        $this->tree = [];
        $previousRow = null;

        foreach ($normalTable as $row) {
            $dimensions = $row->getTuple();

            $columnNumber = 0;
            $sameAsPrevious = $previousRow !== null && $previousRow->hasSameTuple($row);

            foreach ($dimensions as $dimension) {
                // if last dimension
                if ($columnNumber === \count($dimensions) - 1) {
                    $this->addMeasure($dimension, $row->getMeasure(), $columnNumber);

                    break;
                }

                if (!$sameAsPrevious) {
                    $this->addDimension($dimension, $columnNumber, true);
                }

                $columnNumber++;
            }

            $previousRow = $row;
        }

        return $this->tree;
    }
}
