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

use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\DimensionCollector\UniqueDimensions;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTree;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTreeNode;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;

final class NormalTableToTreeTransformer
{
    /**
     * @var list<DefaultTreeNode>
     */
    private array $currentPath = [];

    /**
     * @var list<DefaultTreeNode>
     */
    private array $tree = [];

    /**
     * @param list<string> $keys
     */
    public function __construct(
        private readonly SummaryQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly array $keys,
        private readonly UniqueDimensions $uniqueDimensions,
    ) {}

    public static function transform(
        SummaryQuery $query,
        SummaryMetadata $metadata,
        DefaultNormalTable $normalTable,
    ): DefaultTree {
        // check if empty

        $firstRow = $normalTable->first();

        if ($firstRow === null) {
            return new DefaultTree(
                childrenKey: null,
                summaryClass: $normalTable->getSummaryClass(),
                children: [],
                uniqueDimensions: $normalTable->getUniqueDimensions(),
            );
        }

        // get keys from the first row

        $members = $firstRow->getTuple()->getMembers();
        $keys = array_keys($members);

        // instantiate and process

        $transformer = new self(
            query: $query,
            metadata: $metadata,
            keys: $keys,
            uniqueDimensions: $normalTable->getUniqueDimensions(),
        );

        return new DefaultTree(
            childrenKey: $keys[0],
            summaryClass: $normalTable->getSummaryClass(),
            children: $transformer->doTransform($normalTable),
            uniqueDimensions: $normalTable->getUniqueDimensions(),
        );
    }

    private function hasTieredOrder(): bool
    {
        $orderBy = $this->query->getOrderBy();

        if (\count($orderBy) === 0) {
            return true;
        }

        $orderFields = array_keys($orderBy);

        $dimensionWithoutValues = array_filter(
            $this->metadata->getDimensionPropertyNames(),
            fn(string $dimension): bool => $dimension !== '@values',
        );

        return $orderFields === $dimensionWithoutValues;
    }

    private function addDimension(
        DefaultDimension $dimension,
        int $columnNumber,
        bool $forceCreate,
    ): void {
        $childrenKey = $this->keys[$columnNumber + 1] ?? null;

        if ($childrenKey === null) {
            throw new UnexpectedValueException('Children key cannot be null');
        }

        $node = DefaultTreeNode::createBranchNode(
            childrenKey: $childrenKey,
            dimension: $dimension,
            uniqueDimensions: $this->uniqueDimensions,
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
        DefaultDimension $lastDimension,
        DefaultMeasure $measure,
        int $columnNumber,
    ): void {
        $node = DefaultTreeNode::createLeafNode(
            dimension: $lastDimension,
            uniqueDimensions: $this->uniqueDimensions,
            measure: $measure,
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
    private function doTransform(DefaultNormalTable $normalTable): array
    {
        if ($this->hasTieredOrder()) {
            return $this->transformToTree($normalTable);
        } else {
            return $this->transformToTable($normalTable);
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
