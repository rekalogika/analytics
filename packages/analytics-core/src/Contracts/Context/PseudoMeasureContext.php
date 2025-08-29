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

namespace Rekalogika\Analytics\Contracts\Context;

use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;

final readonly class PseudoMeasureContext
{
    public function __construct(
        private MeasureMetadata $measureMetadata,
        private Coordinates $coordinates,
    ) {}

    public function getMeasureMetadata(): MeasureMetadata
    {
        return $this->measureMetadata;
    }

    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }
}
