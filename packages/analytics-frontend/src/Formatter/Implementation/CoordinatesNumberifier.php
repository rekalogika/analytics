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

namespace Rekalogika\Analytics\Frontend\Formatter\Implementation;

use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;

final readonly class CoordinatesNumberifier implements Numberifier
{
    #[\Override]
    public function toNumber(mixed $input): int|float
    {
        if (!$input instanceof Coordinates) {
            throw new ValueNotSupportedException();
        }

        return 0;
    }
}
