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

namespace Rekalogika\Analytics\Frontend\Formatter\Property;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Formatter\CellifierAware;
use Rekalogika\Analytics\Frontend\Formatter\CellProperties;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;
use Rekalogika\Analytics\PivotTable\Model\Property;

final readonly class PropertyCellifier implements Cellifier, CellifierAware
{
    public function __construct(
        private ?Cellifier $cellifier = null,
    ) {}

    #[\Override]
    public function withCellifier(Cellifier $cellifier): static
    {
        if ($this->cellifier === $cellifier) {
            return $this;
        }

        return new self($cellifier);
    }

    private function getCellifier(): Cellifier
    {
        return $this->cellifier ?? throw new LogicException('Cellifier is not set.');
    }

    #[\Override]
    public function toCell(mixed $input): CellProperties
    {
        if (!$input instanceof Property) {
            throw new ValueNotSupportedException();
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        return $this->getCellifier()->toCell($content);
    }
}
