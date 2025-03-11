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
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\MeasureDescription;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\MeasureDescriptionFactory;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultUnpivotRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;
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

    /**
     * @param iterable<ResultRow> $input
     * @return iterable<ResultUnpivotRow>
     */
    public static function transform(
        SummaryQuery $summaryQuery,
        iterable $input,
        SummaryMetadata $metadata,
        TranslatableInterface $valuesLabel = new TranslatableMessage('Values'),
    ): iterable {
        $transformer = new self(
            summaryQuery: $summaryQuery,
            metadata: $metadata,
            valuesLabel: $valuesLabel,
        );

        return $transformer->doTransform($input);
    }

    /**
     * @param iterable<ResultRow> $input
     * @return iterable<ResultUnpivotRow>
     */
    private function doTransform(iterable $input): iterable
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

        return $rows;
    }

    private function getMeasureSorterCallable(): callable
    {
        $measures = array_flip($this->measures);

        return function (ResultUnpivotRow $row1, ResultUnpivotRow $row2) use ($measures): int {
            return ResultUnpivotRow::compare($row1, $row2, $measures);
        };
    }

    /**
     * @return iterable<ResultUnpivotRow>
     */
    private function unpivotRow(ResultRow $row): iterable
    {
        $newRow = [];

        foreach ($this->dimensions as $dimension) {
            if ($dimension === '@values') {
                // temporary value, we book the place in the row, the value will
                // be set in the next loop
                $newRow['@values'] = true;
            } else {
                /** @psalm-suppress MixedAssignment */
                $newRow[$dimension] = $row->getDimensionMember($dimension);
            }
        }

        if ($newRow === []) {
            throw new \RuntimeException('No dimensions found in row');
        }

        foreach ($this->measures as $measure) {
            // @values represent the place of the value column in the
            // row. the value column is not always at the end of the row

            $measureLabel = $this->getMeasureDescription($measure);

            /** @todo change to dedicated class for values */
            $newRow['@values'] = new ResultValue(
                field: '@values',
                rawValue: $measureLabel,
                value: $measureLabel,
                label: $this->valuesLabel,
                numericValue: 0,
            );

            /** @var non-empty-array<string,ResultValue> $newRow */

            yield new ResultUnpivotRow(
                object: $row->getObject(),
                dimensions: $newRow,
                measure: $row->getMeasure($measure),
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
