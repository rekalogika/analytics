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

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\NumberifierAware;
use Rekalogika\Analytics\Frontend\Formatter\Unsupported;

final readonly class ChainNumberifier implements Numberifier
{
    /**
     * @var list<Numberifier>
     */
    private array $numberifiers;

    /**
     * @param iterable<Numberifier> $numberifiers
     */
    public function __construct(
        iterable $numberifiers,
    ) {
        $newNumberifiers = [];

        foreach ($numberifiers as $numberifier) {
            if ($numberifier instanceof NumberifierAware) {
                $numberifier = $numberifier->withNumberifier($this);
            }

            $newNumberifiers[] = $numberifier;
        }

        $this->numberifiers = $newNumberifiers;
    }

    #[\Override]
    public function toNumber(mixed $input): int|float
    {
        foreach ($this->numberifiers as $numberifier) {
            try {
                return $numberifier->toNumber($input);
            } catch (Unsupported) {
            }
        }

        throw new InvalidArgumentException(\sprintf(
            'Cannot convert "%s" to a number. To fix this problem, you need to create a custom implementation of "Numberifier" for "%s".',
            get_debug_type($input),
            get_debug_type($input),
        ));
    }
}
