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

use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultNormalRow;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTree;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTreeNode;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTuple;

final class TreeToBalancedNormalTableTransformer
{
    /**
     * @var list<DefaultNormalRow>
     */
    private array $rows = [];

    public function __construct(
        private readonly DefaultTree $tree,
    ) {}

    public static function transform(DefaultTree $tree): DefaultNormalTable
    {
        $transformer = new self($tree);
        $rows = $transformer->process();

        return new DefaultNormalTable(
            summaryClass: $tree->getSummaryClass(),
            rows: $rows,
            itemCollection: $tree->getItemCollection(),
            rowCollection: $tree->getRowCollection(),
            condition: $tree->getTuple()->getCondition(),
        );
    }

    /**
     * @return list<DefaultNormalRow>
     */
    private function process(): array
    {
        foreach ($this->tree as $node) {
            $this->processNode($node, []);
        }

        return $this->rows;
    }

    /**
     * @param list<DefaultTreeNode> $currentRow
     */
    private function processNode(DefaultTreeNode $node, array $currentRow): void
    {
        $currentRow[] = $node;

        if (\count($node) === 0) {
            $measure = $node->getMeasure();

            if ($measure === null) {
                throw new UnexpectedValueException('Measure is null');
            }

            $this->createRow($currentRow, $measure);
        }

        foreach ($node as $child) {
            $this->processNode($child, $currentRow);
        }
    }

    /**
     * @param list<DefaultTreeNode> $currentRow
     */
    private function createRow(array $currentRow, DefaultMeasure $measure): void
    {
        $dimensions = array_map(
            static fn(DefaultTreeNode $node): DefaultDimension => $node->getDimension(),
            $currentRow,
        );

        $tuple = new DefaultTuple(
            summaryClass: $this->tree->getSummaryClass(),
            dimensions: $dimensions,
            condition: $this->tree->getTuple()->getCondition(),
        );

        $this->rows[] = new DefaultNormalRow(
            tuple: $tuple,
            measure: $measure,
            groupings: null,
        );
    }
}
