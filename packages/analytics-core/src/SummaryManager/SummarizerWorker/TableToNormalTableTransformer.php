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
use Rekalogika\Analytics\SummaryManager\DefaultQuery;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\DimensionCollector;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimensions;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasureMember;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTable;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

final class TableToNormalTableTransformer
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
     * @var array<string,DefaultMeasureMember>
     */
    private array $measureMemberCache = [];

    private DimensionCollector $dimensionCollector;

    private function __construct(
        DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        bool $hasTieredOrder,
        private readonly TranslatableInterface $measureLabel = new TranslatableMessage('Values'),
    ) {
        $dimensions = $query->getGroupBy();

        if (!\in_array('@values', $dimensions, true)) {
            $dimensions[] = '@values';
        }

        $this->dimensions = $dimensions;
        $this->measures = $query->getSelect();
        $this->dimensionCollector = new DimensionCollector($hasTieredOrder);
    }

    public static function transform(
        DefaultQuery $query,
        DefaultTable $input,
        SummaryMetadata $metadata,
        bool $hasTieredOrder,
        TranslatableInterface $valuesLabel = new TranslatableMessage('Values'),
    ): DefaultNormalTable {
        $transformer = new self(
            query: $query,
            metadata: $metadata,
            measureLabel: $valuesLabel,
            hasTieredOrder: $hasTieredOrder,
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
                $this->dimensionCollector->processDimensions($row2->getDimensions());
                $this->dimensionCollector->processMeasure($row2->getMeasure());
            }
        }

        /** @psalm-suppress MixedArgumentTypeCoercion */
        usort($rows, $this->getMeasureSorterCallable());

        $uniqueDimensions = $this->dimensionCollector->getResult();

        return new DefaultNormalTable(
            rows: $rows,
            uniqueDimensions: $uniqueDimensions,
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
                $newRow[$dimension] = $row->getDimensions()->get($dimension);
            }
        }

        // @phpstan-ignore identical.alwaysFalse
        if ($newRow === []) {
            throw new UnexpectedValueException('No dimensions found in row');
        }

        foreach ($this->measures as $measure) {
            // @values represent the place of the value column in the
            // row. the value column is not always at the end of the row

            $measureValue = $this->getMeasureMember($measure);

            $newRow['@values'] = DefaultDimension::createMeasureDimension(
                label: $this->measureLabel,
                measureMember: $measureValue,
            );

            /** @var array<string,DefaultDimension> $newRow */
            $dimensions = new DefaultDimensions($newRow);

            $measure = $row->getMeasures()->get($measure)
                ?? throw new UnexpectedValueException(
                    \sprintf('Measure "%s" not found in row', $measure),
                );

            yield new DefaultNormalRow(
                dimensions: $dimensions,
                measure: $measure,
                groupings: $row->getGroupings(),
            );
        }
    }

    private function getMeasureMember(string $measure): DefaultMeasureMember
    {
        return $this->measureMemberCache[$measure] ??= new DefaultMeasureMember(
            label: $this->metadata->getMeasureMetadata($measure)->getLabel(),
            property: $measure,
        );
    }
}
