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

enum MismatchedThresholdsBin: int implements RangeBin
{
    use RangeBinTrait;

    case A = 1;
    case B = 2;
    case C = 3;

    #[\Override]
    public static function getThresholds(): array
    {
        return [10];
    }
}
