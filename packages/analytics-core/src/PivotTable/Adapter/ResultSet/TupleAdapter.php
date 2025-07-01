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

namespace Rekalogika\Analytics\PivotTable\Adapter\ResultSet;

use Rekalogika\Analytics\Contracts\Result\Tuple as AnalyticsTuple;
use Rekalogika\PivotTable\Contracts\Result\Tuple;

/**
 * @implements \IteratorAggregate<FieldAdapter>
 */
final readonly class TupleAdapter implements Tuple, \IteratorAggregate
{
    public function __construct(
        private AnalyticsTuple $tuple,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->tuple as $dimension) {
            yield new FieldAdapter($dimension);
        }
    }

    #[\Override]
    public function count(): int
    {
        return $this->tuple->count();
    }
}
