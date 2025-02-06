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
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\MeasureDescriptionFactory;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;

final class MeasureSorter
{
    /**
     * @var list<string>
     */
    private readonly array $measures;

    /**
     * @var list<string>
     */
    private array $keysBeforeValues = [];

    public function __construct(
        SummaryQuery $summaryQuery,
        private readonly SummaryMetadata $metadata,
    ) {
        $this->measures = $summaryQuery->getSelect();
    }

    /**
     * @param iterable<array<string,array{mixed,mixed}|string>> $input
     * @return iterable<array<string,array{mixed,mixed}>>
     */
    public function sortMeasures(iterable $input): iterable
    {
        /**
         * @psalm-suppress InvalidArgument
         * @var list<array<string, mixed>>
         */
        $input = iterator_to_array($input, false);
        $this->calculateKeysBeforeValues($input);

        usort($input, $this->sortByMeasures(...));

        /** @var array<string,array{mixed,mixed}> $row */
        foreach ($input as $row) {
            yield $this->replaceMeasureByLabel($row);
        }
    }

    /**
     * @param list<array<string,mixed>> $input
     */
    private function calculateKeysBeforeValues(array $input): void
    {
        $firstRow = $input[0] ?? [];

        foreach (array_keys($firstRow) as $key) {
            if ($key === '@values') {
                break;
            }

            $this->keysBeforeValues[] = $key;
        }
    }

    /**
     * @param array<string,mixed> $input1
     * @param array<string,mixed> $input2
     * @return 0|1|-1
     */
    private function sortByMeasures(array $input1, array $input2): int
    {
        foreach ($this->keysBeforeValues as $key) {
            if ($input1[$key] !== $input2[$key]) {
                return 0;
            }
        }

        $measures = array_flip($this->measures);

        /** @psalm-suppress MixedAssignment */
        $measure1 = $input1['@values'];
        /** @psalm-suppress MixedAssignment */
        $measure2 = $input2['@values'];

        /** @psalm-suppress MixedArrayOffset */
        $measureValue1 = $measures[$measure1]
            ?? throw new \RuntimeException('Measure value not found');

        /** @psalm-suppress MixedArrayOffset */
        $measureValue2 = $measures[$measure2]
            ?? throw new \RuntimeException('Measure value not found');

        return $measureValue1 <=> $measureValue2;
    }

    /**
     * @param array<string,array{mixed,mixed}|string> $row
     * @return array<string,array{mixed,mixed}>
     */
    private function replaceMeasureByLabel(array $row): array
    {
        $measure = $row['@values'] ?? null;

        if (!\is_string($measure)) {
            throw new \RuntimeException('Measure not found');
        }

        $label = $this->metadata->getMeasureMetadata($measure)->getLabel();

        $label = MeasureDescriptionFactory::createMeasureDescription(
            measurePropertyName: $measure,
            label: $label,
        );

        $row['@values'] = [$label, $label];

        /** @var array<string,array{mixed,mixed}> $row */
        return $row;
    }
}
