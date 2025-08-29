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

namespace Rekalogika\Analytics\Core\PseudoMeasure;

use Rekalogika\Analytics\Contracts\Context\PseudoMeasureContext;
use Rekalogika\Analytics\Contracts\Summary\PseudoMeasure;

/**
 * A virtual measure that returns the coordinates of the current aggregation
 */
final readonly class CoordinatesMeasure implements PseudoMeasure
{
    #[\Override]
    public function createPseudoMeasure(PseudoMeasureContext $context): mixed
    {
        return $context->getCoordinates();
    }
}
