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

use Rekalogika\Analytics\SummaryManager\SummaryQuery;

final readonly class UnpivotValuesTransformer
{
    /**
     * @var list<string>
     */
    private array $dimensions;

    /**
     * @var list<string>
     */
    private array $measures;

    public function __construct(
        SummaryQuery $summaryQuery,
    ) {
        $this->dimensions = $summaryQuery->getGroupBy();
        $this->measures = $summaryQuery->getSelect();
    }

    /**
     * @param iterable<array<string,array{mixed,mixed}>> $input
     * @return iterable<array<string,array{mixed,mixed}|string>>
     */
    public function unpivot(iterable $input): iterable
    {
        foreach ($input as $row) {
            foreach ($this->unpivotRow($row) as $row2) {
                yield $row2;
            }
        }
    }

    /**
     * @param array<string,array{mixed,mixed}> $row
     * @return iterable<array<string,array{mixed,mixed}|string>>
     */
    private function unpivotRow(array $row): iterable
    {
        $newRow = [];

        foreach ($this->dimensions as $dimension) {
            if ($dimension === '@values') {
                $newRow['@values'] = true;
                // temporary value
            } elseif (\array_key_exists($dimension, $row)) {
                $newRow[$dimension] = $row[$dimension];
            } else {
                throw new \RuntimeException(\sprintf('Dimension %s not found in row', $dimension));
            }
        }

        foreach ($this->measures as $measure) {
            // $measureLabel = $this->metadata->getMeasureMetadata($measure)->getLabel();
            $newRow['@values'] = $measure;
            $newRow['@measure'] = $row[$measure]
                ?? throw new \RuntimeException(\sprintf('Measure %s not found in row', $measure));

            /** @var array<string,array{mixed,mixed}|string> $newRow */

            yield $newRow;
        }
    }
}
