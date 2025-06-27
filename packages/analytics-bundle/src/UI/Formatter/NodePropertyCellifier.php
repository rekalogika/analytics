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

namespace Rekalogika\Analytics\Bundle\UI\Formatter;

use Rekalogika\Analytics\Bundle\Formatter\BackendCellifier;
use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\Bundle\Formatter\CellifierAware;
use Rekalogika\Analytics\Bundle\Formatter\CellProperties;
use Rekalogika\Analytics\PivotTable\Model\Property;

final readonly class NodePropertyCellifier implements BackendCellifier, CellifierAware
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

    #[\Override]
    public function toCell(mixed $input): ?CellProperties
    {
        if (!$input instanceof Property) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        return $this->cellifier?->toCell($content);
    }
}
