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
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;

/**
 * A stringifier for Coordinates objects that returns an empty string. If you
 * need to render the coordinates, you need to create your custom implementation
 * to handle Coordinates objects.
 */
final readonly class CoordinatesStringifier implements Stringifier
{
    #[\Override]
    public function toString(mixed $input): string
    {
        if ($input instanceof Coordinates) {
            return '';
        }

        throw new ValueNotSupportedException();
    }
}
