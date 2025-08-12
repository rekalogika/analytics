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

namespace Rekalogika\PivotTable\Block\Model;

use Rekalogika\PivotTable\Contracts\Cube\Dimension;
use Rekalogika\PivotTable\Contracts\Cube\SubtotalDescriptionResolver;

final readonly class SubtotalDimensionDecorator implements Dimension
{
    public function __construct(
        private Dimension $dimension,
        private SubtotalDescriptionResolver $subtotalDescriptionResolver,
    ) {}

    #[\Override]
    public function getName(): string
    {
        return $this->dimension->getName();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->dimension->getLegend();
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->subtotalDescriptionResolver
            ->getSubtotalDescription($this->dimension->getName());
    }
}
