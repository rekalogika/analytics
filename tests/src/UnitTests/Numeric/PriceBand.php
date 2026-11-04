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

namespace Rekalogika\Analytics\Tests\UnitTests\Numeric;

use Rekalogika\Analytics\Numeric\Bin\Trait\RangeBinTrait;
use Rekalogika\Analytics\Numeric\RangeBin;

/**
 * Integer-backed, with a float threshold, and backing values that are not
 * consecutive.
 */
enum PriceBand: int implements RangeBin
{
    use RangeBinTrait;

    case Cheap = 10;
    case Moderate = 20;
    case Expensive = 30;
    case Luxury = 40;

    #[\Override]
    public static function getThresholds(): array
    {
        return [1000, 2500.5, 5000];
    }
}
