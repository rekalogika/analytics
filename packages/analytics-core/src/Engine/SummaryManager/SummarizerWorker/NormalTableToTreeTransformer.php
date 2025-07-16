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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker;

use Rekalogika\Analytics\Common\Exception\EmptyResultException;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\ItemCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTree;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTreeNode;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTreeNodeFactory;
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
     * @param class-string $summaryClass
     * @param list<string> $names
     */
    public function __construct(
        private readonly string $summaryClass,
        private readonly array $names,
        private readonly ItemCollection $itemCollection,
        private readonly DefaultTreeNodeFactory $treeNodeFactory,
    ) {}

    public static function transform(
        TranslatableInterface $label,
        DefaultNormalTable $normalTable,
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
                itemCollection: $normalTable->getItemCollection(),
                treeNodeFactory: $treeNodeFactory,
            );
        }

        // get keys from the first row

        $members = $firstRow->getMembers();
        $names = array_keys($members);

        // instantiate and process

        $transformer = new self(
            summaryClass: $summaryClass,
            names: $names,
            itemCollection: $normalTable->getItemCollection(),
            treeNodeFactory: $treeNodeFactory,
        );

        return new DefaultTree(
            summaryClass: $summaryClass,
            label: $label,
            childrenKey: $names[0],
            children: $transformer->doTransform($normalTable),
            itemCollection: $normalTable->getItemCollection(),
            treeNodeFactory: $treeNodeFactory,
        );
    }

    private function addDimension(
        ?DefaultTreeNode $parent,
        DefaultDimension $dimension,
        int $columnNumber,
    ): DefaultTreeNode {
        $childrenKey = $this->names[$columnNumber + 1] ?? null;

        if ($childrenKey === null) {
            throw new UnexpectedValueException('Children key cannot be null');
        }

        return $this->treeNodeFactory->createBranchNode(
            summaryClass: $this->summaryClass,
            childrenKey: $childrenKey,
            parent: $parent,
            dimension: $dimension,
            itemCollection: $this->itemCollection,
        );
    }

    private function addMeasure(
        ?DefaultTreeNode $parent,
        DefaultDimension $dimension,
        DefaultMeasure $measure,
    ): DefaultTreeNode {
        return $this->treeNodeFactory->createLeafNode(
            summaryClass: $this->summaryClass,
            dimension: $dimension,
            parent: $parent,
            itemCollection: $this->itemCollection,
            measure: $measure,
        );
    }

    /**
     * @return list<DefaultTreeNode>
     */
    private function doTransform(DefaultNormalTable $normalTable): array
    {
        $this->currentPath = [];
        $this->tree = [];

        foreach ($normalTable as $row) {
            $columnNumber = 0;

            foreach ($row as $dimension) {
                $currentPosInPath = $this->currentPath[$columnNumber] ?? null;

                if ($currentPosInPath !== null && $currentPosInPath->isEqual($dimension)) {
                    $columnNumber++;
                    continue;
                }

                if ($columnNumber > 0) {
                    $previous = $this->currentPath[$columnNumber - 1] ?? null;
                } else {
                    $previous = null;
                }

                // if last dimension
                if ($columnNumber === \count($row) - 1) {
                    $node = $this->addMeasure(
                        parent: $previous,
                        dimension: $dimension,
                        measure: $row->getMeasure(),
                    );
                } else {
                    $node = $this->addDimension(
                        parent: $previous,
                        dimension: $dimension,
                        columnNumber: $columnNumber,
                    );
                }

                if ($columnNumber === 0) {
                    $this->currentPath = [$node];
                    $this->tree[] = $node;

                    $columnNumber++;
                    continue;
                }


                $currentPath = \array_slice($this->currentPath, 0, $columnNumber);
                $currentPath[$columnNumber] = $node;
                $this->currentPath = array_values($currentPath);

                $columnNumber++;
            }
        }

        return $this->tree;
    }
}
