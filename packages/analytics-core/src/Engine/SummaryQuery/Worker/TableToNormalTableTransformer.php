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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Worker;

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultDimension;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultMeasureMember;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultNormalRow;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultNormalTable;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultRow;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultTable;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultTuple;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
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

    private function __construct(
        DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly ResultContext $context,
        private readonly TranslatableInterface $measureLabel,
    ) {
        $dimensions = $query->getGroupBy();

        if (!\in_array('@values', $dimensions, true)) {
            $dimensions[] = '@values';
        }

        $this->dimensions = $dimensions;
        $this->measures = $query->getSelect();
    }

    public static function transform(
        DefaultQuery $query,
        DefaultTable $table,
        SummaryMetadata $metadata,
        TranslatableInterface $valuesLabel = new TranslatableMessage('Values'),
    ): DefaultNormalTable {
        $transformer = new self(
            query: $query,
            metadata: $metadata,
            context: $table->getContext(),
            measureLabel: $valuesLabel,
        );

        return new DefaultNormalTable(
            summaryClass: $metadata->getSummaryClass(),
            rows: $transformer->doTransform($table),
            context: $table->getContext(),
        );
    }

    /**
     * @return iterable<DefaultNormalRow>
     */
    private function doTransform(DefaultTable $table): iterable
    {
        $rows = [];

        foreach ($table as $row) {
            if ($row->isGrouping()) {
                continue;
            }

            foreach ($this->unpivotRow($row) as $normalRow) {
                $rows[] = $normalRow;
            }
        }

        /** @psalm-suppress MixedArgumentTypeCoercion */
        usort($rows, $this->getMeasureSorterCallable());

        yield from $rows;
    }

    private function getMeasureSorterCallable(): callable
    {
        $measures = array_flip($this->measures);

        return fn(DefaultNormalRow $row1, DefaultNormalRow $row2): int => DefaultNormalRow::compare($row1, $row2, $measures);
    }

    /**
     * @return iterable<DefaultNormalRow>
     */
    private function unpivotRow(DefaultRow $row): iterable
    {
        $summaryClass = $row->getTuple()->getSummaryClass();
        $newRow = [];

        foreach ($this->dimensions as $dimension) {
            if ($dimension === '@values') {
                // temporary value, we book the place in the row, the value will
                // be set in the next loop
                $newRow['@values'] = true;

                continue;
            }

            $d = $row->getTuple()->getByKey($dimension);

            if ($d === null) {
                continue;
            }

            $newRow[$dimension] = $d;
        }

        // @phpstan-ignore identical.alwaysFalse
        if ($newRow === []) {
            throw new UnexpectedValueException('No dimensions found in row');
        }

        foreach ($this->measures as $measure) {
            // @values represent the place of the value column in the
            // row. the value column is not always at the end of the row

            $measureMember = $this->getMeasureMember($measure);

            $newRow['@values'] = $this->context
                ->getDimensionFactory()
                ->createDimension(
                    label: $this->measureLabel,
                    name: '@values',
                    member: $measureMember,
                    rawMember: $measureMember,
                    displayMember: $measureMember,
                    interpolation: false,
                );

            /** @var array<string,DefaultDimension> $newRow */
            $tuple = new DefaultTuple(
                summaryClass: $summaryClass,
                dimensions: $newRow,
                condition: $row->getTuple()->getCondition(),
            );

            $measure = $row->getMeasures()->getByKey($measure)
                ?? throw new UnexpectedValueException(
                    \sprintf('Measure "%s" not found in row', $measure),
                );

            yield new DefaultNormalRow(
                tuple: $tuple,
                measure: $measure,
                groupings: $row->getGroupings(),
            );
        }
    }

    private function getMeasureMember(string $measure): DefaultMeasureMember
    {
        return $this->measureMemberCache[$measure] ??= new DefaultMeasureMember(
            label: $this->metadata->getMeasure($measure)->getLabel(),
            property: $measure,
        );
    }
}
