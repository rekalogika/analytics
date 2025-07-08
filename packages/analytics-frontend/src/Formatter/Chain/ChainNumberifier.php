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

namespace Rekalogika\Analytics\Frontend\Formatter\Chain;

use Rekalogika\Analytics\Frontend\Formatter\Exception\NumberifierFailureException;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\NumberifierAware;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;

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
            } catch (ValueNotSupportedException) {
            }
        }

        throw new NumberifierFailureException($input);
    }
}
