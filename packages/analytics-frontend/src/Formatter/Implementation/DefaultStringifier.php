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

use Rekalogika\Analytics\Frontend\Formatter\Stringifier;

final readonly class DefaultStringifier implements Stringifier
{
    #[\Override]
    public function toString(mixed $input): string
    {
        if ($input instanceof \Stringable) {
            return (string) $input;
        }

        if (\is_string($input)) {
            return $input;
        }

        if (\is_int($input)) {
            return (string) $input;
        }

        if (\is_float($input)) {
            return (string) $input;
        }

        if ($input instanceof \BackedEnum) {
            return $input->name;
        }

        if ($input instanceof \UnitEnum) {
            return $input->name;
        }

        if ($input === null) {
            return '-';
        }

        // Do not leak internal class names to the user interface.
        if (\is_object($input)) {
            return 'object';
        }

        return get_debug_type($input);
    }
}
