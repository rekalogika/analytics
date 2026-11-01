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

namespace Rekalogika\Analytics\Metadata\Attribute;

use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;

/**
 * Marks the partition property of a summary class. The property must be a
 * Doctrine embeddable whose class implements the `Partition` contract, e.g.
 * `DefaultIntegerPartition`. Exactly one property of a summary class must have
 * this attribute.
 *
 * ```php
 * #[ORM\Embedded]
 * #[Analytics\Partition(new IntegerValue('id'))]
 * private DefaultIntegerPartition $partition;
 * ```
 *
 * Partitioning lets the framework refresh only the part of the summary table
 * affected by a change in the source, see the `Partition` contract.
 *
 * @see \Rekalogika\Analytics\Contracts\Model\Partition
 *
 * @template T The type of the partitioning value in the source entity.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Partition
{
    /**
     * @param PartitionValueResolver<T> $source Resolves the partitioning
     * value of the source entity, typically its primary key.
     */
    public function __construct(
        private PartitionValueResolver $source,
    ) {}

    /**
     * The resolver of the partitioning value of the source entity.
     *
     * @return PartitionValueResolver<T>
     */
    public function getSource(): PartitionValueResolver
    {
        return $this->source;
    }
}
