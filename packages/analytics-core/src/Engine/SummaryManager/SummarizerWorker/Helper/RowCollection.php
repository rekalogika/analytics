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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasures;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultNormalRow;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultRow;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTuple;

final class RowCollection
{
    /**
     * @var array<string,DefaultRow>
     */
    private array $rows = [];

    /**
     * @var array<string,DefaultNormalRow>
     */
    private array $normalRows = [];

    public function __construct() {}

    public function collectRow(DefaultRow $row): void
    {
        $signature = $row->getSignature();

        if (isset($this->rows[$signature])) {
            throw new LogicException(
                \sprintf('Row with signature "%s" already exists.', $signature),
            );
        }

        $this->rows[$signature] = $row;
    }

    public function collectNormalRow(DefaultNormalRow $row): void
    {
        $signature = $row->getSignature();

        if (isset($this->normalRows[$signature])) {
            throw new LogicException(
                \sprintf('Normal row with signature "%s" already exists.', $signature),
            );
        }

        $this->normalRows[$signature] = $row;
    }

    public function getByTuple(DefaultTuple $tuple): null|DefaultRow|DefaultNormalRow
    {
        $signature = $tuple->getSignature();

        return $this->rows[$signature]
            ?? $this->normalRows[$signature]
            ?? null;
    }

    public function getMeasures(DefaultTuple $tuple): DefaultMeasures
    {
        $row = $this->getByTuple($tuple);

        if ($row === null) {
            return new DefaultMeasures([]);
        }

        if ($row instanceof DefaultRow) {
            return $row->getMeasures();
        }

        return new DefaultMeasures([$row->getMeasure()]);
    }

    /**
     * @return iterable<DefaultRow>
     */
    public function getRows(): iterable
    {
        foreach ($this->rows as $row) {
            yield $row;
        }
    }
}
