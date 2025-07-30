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
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultRow;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTuple;

final class RowCollection
{
    /**
     * @var array<string,DefaultRow>
     */
    private array $rows = [];

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

    public function getByTuple(DefaultTuple $tuple): null|DefaultRow
    {
        $signature = $tuple->getSignature();

        return $this->rows[$signature] ?? null;
    }

    public function getMeasure(DefaultTuple $tuple): ?DefaultMeasure
    {
        $measureName = $tuple->getMeasureName();

        if ($measureName === null) {
            return null;
        }

        $row = $this->getByTuple($tuple->withoutMeasure());

        if ($row === null) {
            return null;
        }

        return $row->getMeasures()->getByKey($measureName);
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
