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

use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultMeasures;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultNormalTable;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultRow;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultTable;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultTuple;

final class BalancedNormalTableToBalancedTableTransformer
{
    public static function transform(DefaultNormalTable $normalTable): DefaultTable
    {
        $transformer = new self($normalTable);

        return $transformer->process();
    }

    private function __construct(
        private readonly DefaultNormalTable $normalTable,
    ) {}

    /**
     * @var array<string,DefaultTuple>
     */
    private array $signatureToTuple = [];

    /**
     * @var array<string,list<DefaultMeasure>>
     */
    private array $signatureToMeasures = [];

    /**
     * @var list<DefaultRow>
     */
    private array $rows = [];

    private function process(): DefaultTable
    {
        $summaryClass = $this->normalTable->getSummaryClass();

        foreach ($this->normalTable as $currentRow) {
            $tupleWithoutValues = $currentRow->getWithoutValues();
            $signature = $tupleWithoutValues->getSignature();

            $this->signatureToTuple[$signature] ??= $tupleWithoutValues;
            $this->signatureToMeasures[$signature][] = $currentRow->getMeasure();
        }

        foreach ($this->signatureToTuple as $signature => $tuple) {
            $measures = $this->signatureToMeasures[$signature] ?? [];
            $measureValues = new DefaultMeasures($measures);

            $row = new DefaultRow(
                tuple: $tuple,
                measures: $measureValues,
                groupings: null,
            );

            $this->rows[] = $row;
        }

        return new DefaultTable(
            summaryClass: $summaryClass,
            rows: $this->rows,
            context: $this->normalTable->getContext(),
        );
    }
}
