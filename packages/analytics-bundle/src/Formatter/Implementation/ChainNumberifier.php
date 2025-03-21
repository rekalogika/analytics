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
use Rekalogika\Analytics\Bundle\Formatter\Numberifier;

final readonly class ChainNumberifier implements Numberifier
{
    /**
     * @param iterable<BackendNumberifier> $backendNumberifiers
     */
    public function __construct(
        private iterable $backendNumberifiers,
    ) {}

    #[\Override]
    public function toNumber(mixed $input): int|float
    {
        foreach ($this->backendNumberifiers as $numberifier) {
            $result = $numberifier->toNumber($input);

            if ($result !== null) {
                return $result;
            }
        }

        return 0;
    }
}
