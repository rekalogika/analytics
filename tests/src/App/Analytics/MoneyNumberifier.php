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

namespace Rekalogika\Analytics\Tests\App\Analytics;

use Brick\Money\Money;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\Unsupported;

final class MoneyNumberifier implements Numberifier
{
    #[\Override]
    public function toNumber(mixed $input): float
    {
        if (!$input instanceof Money) {
            throw new Unsupported();
        }

        return $input->getAmount()->toFloat();
    }
}
