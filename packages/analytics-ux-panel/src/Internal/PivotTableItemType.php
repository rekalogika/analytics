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

namespace Rekalogika\Analytics\UX\PanelBundle\Internal;

enum PivotTableItemType: string
{
    case Dimension = 'dimension';
    case MandatoryDimension = 'mandatorydimension';
    case Measure = 'measure';
    case Values = 'values';
}
