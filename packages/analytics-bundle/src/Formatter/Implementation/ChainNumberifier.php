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
use Rekalogika\Analytics\Bundle\Formatter\NumberifierAware;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;

final readonly class ChainNumberifier implements Numberifier
{
    /**
     * @var list<BackendNumberifier>
     */
    private array $backendNumberifiers;

    /**
     * @param iterable<BackendNumberifier> $backendNumberifiers
     */
    public function __construct(
        iterable $backendNumberifiers,
    ) {
        $newBackendNumberifiers = [];

        foreach ($backendNumberifiers as $backendNumberifier) {
            if ($backendNumberifier instanceof NumberifierAware) {
                $backendNumberifier = $backendNumberifier->withNumberifier($this);
            }

            $newBackendNumberifiers[] = $backendNumberifier;
        }

        $this->backendNumberifiers = $newBackendNumberifiers;
    }

    #[\Override]
    public function toNumber(mixed $input): int|float
    {
        foreach ($this->backendNumberifiers as $numberifier) {
            $result = $numberifier->toNumber($input);

            if ($result !== null) {
                return $result;
            }
        }

        throw new InvalidArgumentException(\sprintf(
            'Cannot convert "%s" to a number. To fix this problem, you need to create a custom implementation of "BackendNumberifier" for "%s".',
            get_debug_type($input),
            get_debug_type($input),
        ));
    }
}
