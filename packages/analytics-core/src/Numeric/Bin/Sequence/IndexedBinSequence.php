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

namespace Rekalogika\Analytics\Numeric\Bin\Sequence;

use Rekalogika\Analytics\Contracts\Model\Sequence;
use Rekalogika\Analytics\Numeric\Bin\IndexedBin;

/**
 * @implements Sequence<IndexedBin>
 * @implements \IteratorAggregate<IndexedBin>
 */
final readonly class IndexedBinSequence implements Sequence, \IteratorAggregate
{
    public function __construct(
        private IndexedBin $first,
        private IndexedBin $last,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        $current = $this->first;

        while ($current !== null) {
            yield $current;

            if ($current === $this->last) {
                break;
            }

            $current = $current->getNext();
        }
    }

    #[\Override]
    public function getFirst(): IndexedBin
    {
        return $this->first;
    }

    #[\Override]
    public function getLast(): IndexedBin
    {
        return $this->last;
    }
}
