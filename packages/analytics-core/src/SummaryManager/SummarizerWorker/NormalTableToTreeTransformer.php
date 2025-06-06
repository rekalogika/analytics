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

use Rekalogika\Analytics\Exception\EmptyResultException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\Items;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTree;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTreeNode;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTreeNodeFactory;
use Symfony\Contracts\Translation\TranslatableInterface;

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
     * @param list<string> $names
     */
    public function __construct(
        private readonly array $names,
        private readonly Items $uniqueDimensions,
        private readonly bool $hasTieredOrder,
        private readonly DefaultTreeNodeFactory $treeNodeFactory,
    ) {}

    public static function transform(
        TranslatableInterface $label,
        DefaultNormalTable $normalTable,
        bool $hasTieredOrder,
        DefaultTreeNodeFactory $treeNodeFactory,
    ): DefaultTree {
        $summaryClass = $normalTable->getSummaryClass();
        // check if empty

        try {
            $firstRow = $normalTable->getRowPrototype();
        } catch (EmptyResultException) {
            return new DefaultTree(
                summaryClass: $summaryClass,
                label: $label,
                childrenKey: null,
                children: [],
                items: $normalTable->getUniqueDimensions(),
                treeNodeFactory: $treeNodeFactory,
            );
        }

        // get keys from the first row

        $members = $firstRow->getMembers();
        $names = array_keys($members);

        // instantiate and process

        $transformer = new self(
            names: $names,
            uniqueDimensions: $normalTable->getUniqueDimensions(),
            hasTieredOrder: $hasTieredOrder,
            treeNodeFactory: $treeNodeFactory,
        );

        return new DefaultTree(
            summaryClass: $summaryClass,
            label: $label,
            childrenKey: $names[0],
            children: $transformer->doTransform($normalTable),
            items: $normalTable->getUniqueDimensions(),
            treeNodeFactory: $treeNodeFactory,
        );
    }

    /**
     * @param class-string $summaryClass
     */
    private function addDimension(
        string $summaryClass,
        DefaultDimension $dimension,
        int $columnNumber,
        bool $forceCreate,
    ): void {
        $parent = $this->currentPath[$columnNumber - 1] ?? null;
        $childrenKey = $this->names[$columnNumber + 1] ?? null;

        if ($childrenKey === null) {
            throw new UnexpectedValueException('Children key cannot be null');
        }

        $node = $this->treeNodeFactory->createBranchNode(
            summaryClass: $summaryClass,
            childrenKey: $childrenKey,
            parent: $parent,
            dimension: $dimension,
            items: $this->uniqueDimensions,
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

        $currentPath = \array_slice($this->currentPath, 0, $columnNumber);
        $currentPath[$columnNumber] = $node;

        $this->currentPath = array_values($currentPath);
    }

    /**
     * @param class-string $summaryClass
     */
    private function addMeasure(
        string $summaryClass,
        DefaultDimension $lastDimension,
        DefaultMeasure $measure,
        int $columnNumber,
    ): void {
        $parent = end($this->currentPath);

        if ($parent === false) {
            $parent = null;
        }

        $node = $this->treeNodeFactory->createLeafNode(
            summaryClass: $summaryClass,
            dimension: $lastDimension,
            parent: $parent,
            items: $this->uniqueDimensions,
            measure: $measure,
        );

        if ($columnNumber === 0) {
            $this->currentPath = [$node];
            $this->tree[] = $node;

            return;
        }

        if ($parent === null) {
            $this->currentPath = [$node];
            $this->tree[] = $node;
        }
    }

    /**
     * @return list<DefaultTreeNode>
     */
    private function doTransform(DefaultNormalTable $normalTable): array
    {
        if ($this->hasTieredOrder) {
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
            $columnNumber = 0;

            foreach ($row as $dimension) {
                // if last dimension
                if ($columnNumber === \count($row) - 1) {
                    $this->addMeasure(
                        summaryClass: $normalTable->getSummaryClass(),
                        lastDimension: $dimension,
                        measure: $row->getMeasure(),
                        columnNumber: $columnNumber,
                    );

                    break;
                }

                $this->addDimension(
                    summaryClass: $normalTable->getSummaryClass(),
                    dimension: $dimension,
                    columnNumber: $columnNumber,
                    forceCreate: false,
                );

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
            $columnNumber = 0;
            $sameAsPrevious = $previousRow !== null && $previousRow->hasSameDimensions($row);

            foreach ($row as $dimension) {
                // if last dimension
                if ($columnNumber === \count($row) - 1) {
                    $this->addMeasure(
                        summaryClass: $normalTable->getSummaryClass(),
                        lastDimension: $dimension,
                        measure: $row->getMeasure(),
                        columnNumber: $columnNumber,
                    );

                    break;
                }

                if (!$sameAsPrevious) {
                    $this->addDimension(
                        summaryClass: $normalTable->getSummaryClass(),
                        dimension: $dimension,
                        columnNumber: $columnNumber,
                        forceCreate: true,
                    );
                }

                $columnNumber++;
            }

            $previousRow = $row;
        }

        return $this->tree;
    }
}
