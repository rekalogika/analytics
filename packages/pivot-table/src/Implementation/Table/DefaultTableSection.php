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

namespace Rekalogika\PivotTable\Implementation\Table;

use Rekalogika\PivotTable\Table\Element;
use Rekalogika\PivotTable\Table\Row;

/**
 * @implements \IteratorAggregate<Row>
 */
abstract class DefaultTableSection implements \IteratorAggregate, \Countable, Element
{
    final public function __construct(
        private readonly DefaultRows $rows,
        private readonly DefaultContext $context,
    ) {}

    #[\Override]
    final public function getContext(): DefaultContext
    {
        return $this->context;
    }

    /**
     * @return \Traversable<DefaultRow>
     */
    #[\Override]
    final public function getIterator(): \Traversable
    {
        return $this->rows->getIterator();
    }

    #[\Override]
    final public function count(): int
    {
        return $this->rows->count();
    }
}
