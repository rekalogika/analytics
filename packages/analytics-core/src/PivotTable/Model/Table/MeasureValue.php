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

namespace Rekalogika\Analytics\PivotTable\Model\Table;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\PivotTable\Model\Value;

final readonly class MeasureValue implements Value
{
    public function __construct(
        private Cell $cell,
        private ?string $measureName = null,
    ) {}

    public function withMeasureName(string $measureName): self
    {
        return new self($this->cell, $measureName);
    }

    #[\Override]
    public function getContent(): mixed
    {
        if ($this->measureName === null) {
            throw new InvalidArgumentException('Measure name must be set before getting content.');
        }

        return $this->cell
            ->getMeasures()
            ->getByKey($this->measureName)
            ?->getValue()
            ?? throw new InvalidArgumentException(
                \sprintf('Measure "%s" not found in the row.', $this->measureName),
            );
    }

    public function getCell(): Cell
    {
        return $this->cell;
    }
}
