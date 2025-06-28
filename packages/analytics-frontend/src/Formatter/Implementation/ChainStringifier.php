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
use Rekalogika\Analytics\Frontend\Formatter\StringifierAware;
use Rekalogika\Analytics\Frontend\Formatter\Unsupported;

final readonly class ChainStringifier implements Stringifier
{
    /**
     * @var list<Stringifier>
     */
    private array $stringifiers;

    /**
     * @param iterable<Stringifier> $stringifiers
     */
    public function __construct(
        iterable $stringifiers,
    ) {
        $newStringifiers = [];

        foreach ($stringifiers as $stringifier) {
            if ($stringifier instanceof StringifierAware) {
                $stringifier = $stringifier->withStringifier($this);
            }

            $newStringifiers[] = $stringifier;
        }

        $this->stringifiers = $newStringifiers;
    }

    #[\Override]
    public function toString(mixed $input): string
    {
        foreach ($this->stringifiers as $stringifier) {
            try {
                return $stringifier->toString($input);
            } catch (Unsupported) {
            }
        }

        return get_debug_type($input);
    }
}
