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

namespace Rekalogika\Analytics\Bundle\Formatter\Implementation;

use Rekalogika\Analytics\Bundle\Formatter\BackendNumberifier;

final readonly class DefaultBackendNumberifier implements BackendNumberifier
{
    #[\Override]
    public function toNumber(mixed $input): null|int|float
    {
        if (\is_int($input) || \is_float($input)) {
            return $input;
        }

        if ($input === null) {
            return 0;
        }

        if ($input instanceof \Stringable) {
            $input = (string) $input;
        }

        if ($input instanceof \BackedEnum) {
            $input = $input->value;
        }

        if (is_numeric($input)) {
            return (float) $input;
        }

        return null;
    }
}
