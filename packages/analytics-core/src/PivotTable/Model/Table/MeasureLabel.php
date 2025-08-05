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
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\PivotTable\Model\Label;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class MeasureLabel implements Label
{
    public function __construct(
        private CubeCell $cell,
        private ?string $measureName = null,
    ) {}

    public function withMeasureName(string $measureName): self
    {
        return new self($this->cell, $measureName);
    }

    #[\Override]
    public function getContent(): TranslatableInterface
    {
        if ($this->measureName === null) {
            throw new InvalidArgumentException('Measure name must be set before getting content.');
        }

        return $this->cell
            ->getMeasures()
            ->getByKey($this->measureName)
            ?->getLabel()
            ?? throw new InvalidArgumentException(
                \sprintf('Measure "%s" not found in the row.', $this->measureName),
            );
    }

    public function getRow(): CubeCell
    {
        return $this->cell;
    }
}
