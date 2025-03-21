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

use Rekalogika\Analytics\Bundle\Formatter\BackendStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;

final readonly class ChainStringifier implements Stringifier
{
    /**
     * @param iterable<BackendStringifier> $backendStringifiers
     */
    public function __construct(
        private iterable $backendStringifiers,
    ) {}

    #[\Override]
    public function toString(mixed $input): string
    {
        foreach ($this->backendStringifiers as $stringifier) {
            $result = $stringifier->toString($input);

            if ($result !== null) {
                return $result;
            }
        }

        return get_debug_type($input);
    }
}
