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

namespace Rekalogika\Analytics\Metadata\Groupings;

use Rekalogika\Analytics\Contracts\Model\GroupByExpressions;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

/**
 * Default implementation of GroupByExpressions.
 *
 * @implements \IteratorAggregate<string,Field|FieldSet|Cube|RollUp|GroupingSet>
 */
final readonly class DefaultGroupByExpressions implements
    GroupByExpressions,
    \IteratorAggregate
{
    /**
     * @param array<string,Field|FieldSet|Cube|RollUp|GroupingSet> $expressions
     */
    public function __construct(
        private array $expressions,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->expressions;
    }
}
