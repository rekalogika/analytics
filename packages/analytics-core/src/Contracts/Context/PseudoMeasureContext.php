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

/**
 * Context given to `PseudoMeasure::createPseudoMeasure()`. It describes the
 * measure being created and the cell it is being created for.
 *
 * @see \Rekalogika\Analytics\Contracts\Summary\PseudoMeasure
 */
final readonly class PseudoMeasureContext
{
    public function __construct(
        private MeasureMetadata $measureMetadata,
        private Coordinates $coordinates,
    ) {}

    /**
     * The metadata of the pseudo-measure being created.
     */
    public function getMeasureMetadata(): MeasureMetadata
    {
        return $this->measureMetadata;
    }

    /**
     * The coordinates of the cell the measure value is being created for.
     */
    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }
}
