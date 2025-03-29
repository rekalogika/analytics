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

use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasures;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTable;

final class BalancedNormalTableToBalancedTableTransformer
{
    public static function transform(DefaultNormalTable $normalTable): DefaultTable
    {
        $transformer = new self($normalTable);
        $table = $transformer->process();

        return $table;
    }

    /**
     * @var list<DefaultRow>
     */
    private array $rows = [];

    /**
     * @var list<DefaultMeasure>
     */
    private array $measures = [];

    public function __construct(
        private readonly DefaultNormalTable $normalTable,
    ) {}

    private function process(): DefaultTable
    {
        $lastRow = null;

        foreach ($this->normalTable as $currentRow) {
            if (
                $lastRow === null
                || $lastRow->getTuple()->isSame($currentRow->getTuple())
            ) {
                $this->measures[] = $currentRow->getMeasure();
            } else {
                $this->measures = [$currentRow->getMeasure()];
            }

            $this->rows[] = new DefaultRow(
                tuple: $currentRow->getTuple(),
                measures: new DefaultMeasures($this->measures),
                groupings: '',
            );

            $lastRow = $currentRow;
        }

        return new DefaultTable(
            summaryClass: $this->normalTable->getSummaryClass(),
            rows: $this->rows,
        );
    }
}
