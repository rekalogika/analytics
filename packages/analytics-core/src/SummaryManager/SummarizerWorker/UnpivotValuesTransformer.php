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

use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Query\Dimension;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\MeasureDescription;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\MeasureDescriptionFactory;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimensions;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTuple;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\ValueDimension;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

final class UnpivotValuesTransformer
{
    /**
     * @var list<string>
     */
    private readonly array $dimensions;

    /**
     * @var list<string>
     */
    private readonly array $measures;

    /**
     * @var array<string,TranslatableInterface|string>
     */
    private array $measureLabelCache = [];

    private MeasureDescriptionFactory $measureDescriptionFactory;

    private function __construct(
        SummaryQuery $summaryQuery,
        private readonly SummaryMetadata $metadata,
        private readonly TranslatableInterface $valuesLabel = new TranslatableMessage('Values'),
    ) {
        $dimensions = $summaryQuery->getGroupBy();

        if (!\in_array('@values', $dimensions, true)) {
            $dimensions[] = '@values';
        }

        $this->dimensions = $dimensions;
        $this->measures = $summaryQuery->getSelect();

        $this->measureDescriptionFactory = new MeasureDescriptionFactory();
    }

    public static function transform(
        SummaryQuery $summaryQuery,
        DefaultTable $input,
        SummaryMetadata $metadata,
        TranslatableInterface $valuesLabel = new TranslatableMessage('Values'),
    ): DefaultNormalTable {
        $transformer = new self(
            summaryQuery: $summaryQuery,
            metadata: $metadata,
            valuesLabel: $valuesLabel,
        );

        return $transformer->doTransform($input);
    }

    private function doTransform(DefaultTable $input): DefaultNormalTable
    {
        $rows = [];

        foreach ($input as $row) {
            if ($row->isSubtotal()) {
                continue;
            }

            foreach ($this->unpivotRow($row) as $row2) {
                $rows[] = $row2;
            }
        }

        /** @psalm-suppress MixedArgumentTypeCoercion */
        usort($rows, $this->getMeasureSorterCallable());

        return new DefaultNormalTable(
            summaryClass: $input->getSummaryClass(),
            rows: $rows,
        );
    }

    private function getMeasureSorterCallable(): callable
    {
        $measures = array_flip($this->measures);

        return function (DefaultNormalRow $row1, DefaultNormalRow $row2) use ($measures): int {
            return DefaultNormalRow::compare($row1, $row2, $measures);
        };
    }

    /**
     * @return iterable<DefaultNormalRow>
     */
    private function unpivotRow(DefaultRow $row): iterable
    {
        $newRow = [];

        foreach ($this->dimensions as $dimension) {
            if ($dimension === '@values') {
                // temporary value, we book the place in the row, the value will
                // be set in the next loop
                $newRow['@values'] = true;
            } else {
                $newRow[$dimension] = $row->getTuple()->get($dimension);
            }
        }

        if ($newRow === []) {
            throw new \RuntimeException('No dimensions found in row');
        }

        foreach ($this->measures as $measure) {
            // @values represent the place of the value column in the
            // row. the value column is not always at the end of the row

            $measureLabel = $this->getMeasureDescription($measure);

            $newRow['@values'] = new ValueDimension(
                summaryClass: $this->metadata->getSummaryClass(),
                valuesLabel: $this->valuesLabel,
                measureLabel: $measureLabel,
            );

            /** @var array<string,Dimension> $newRow */
            $dimensions = new DefaultDimensions($newRow);
            $tuple = new DefaultTuple($dimensions);

            yield new DefaultNormalRow(
                tuple: $tuple,
                measure: $row->getMeasures()->get($measure),
                groupings: $row->getGroupings(),
            );
        }
    }

    private function getMeasureDescription(string $measure): MeasureDescription
    {
        $label = $this->measureLabelCache[$measure]
            ??= $this->metadata->getMeasureMetadata($measure)->getLabel();

        return $this->measureDescriptionFactory->createMeasureDescription(
            measurePropertyName: $measure,
            label: $label,
        );
    }
}
