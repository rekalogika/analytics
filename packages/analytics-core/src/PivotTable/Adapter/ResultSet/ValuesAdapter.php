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

use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\PivotTable\Contracts\Result\Values;

/**
 * @implements \IteratorAggregate<ValueAdapter>
 */
final readonly class ValuesAdapter implements Values, \IteratorAggregate
{
    public function __construct(
        private Measures $measures,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->measures as $measure) {
            yield new ValueAdapter($measure);
        }
    }

    #[\Override]
    public function count(): int
    {
        return $this->measures->count();
    }
}
