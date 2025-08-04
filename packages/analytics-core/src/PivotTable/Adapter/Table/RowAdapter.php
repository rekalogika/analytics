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

namespace Rekalogika\Analytics\PivotTable\Adapter\Table;

use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\PivotTable\Contracts\Row as PivotTableRow;

final readonly class RowAdapter implements PivotTableRow
{
    public function __construct(
        private Row $row,
    ) {}

    #[\Override]
    public function getDimensions(): iterable
    {
        foreach ($this->row->getTuple() as $key => $dimension) {
            yield $key => $dimension->getMember();
        }
    }

    #[\Override]
    public function getMeasures(): iterable
    {
        foreach ($this->row->getMeasures() as $key => $value) {
            yield $key => $value->getValue();
        }
    }


    /**
     * @return array<string,mixed>
     */
    public function getLegends(): array
    {
        $legends = [];

        foreach ($this->row->getTuple() as $key => $dimension) {
            $legends[$key] = $dimension->getLabel();
        }

        foreach ($this->row->getMeasures() as $key => $measure) {
            $legends[$key] = $measure->getLabel();
        }

        return $legends;
    }
}
