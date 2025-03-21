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
use Rekalogika\Analytics\Bundle\Formatter\BackendNumberifier;

final class MoneyNumberifier implements BackendNumberifier
{
    #[\Override]
    public function toNumber(mixed $input): null|float
    {
        if (!$input instanceof Money) {
            return null;
        }

        return $input->getAmount()->toFloat();
    }
}
