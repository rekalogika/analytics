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

/**
 * Marks the property of a partition class that stores the partition key, i.e.
 * the value identifying the partition within its level. Used inside
 * implementations of the `Partition` contract, together with
 * `PartitionLevel`. The framework indexes the summary table by these two
 * columns.
 *
 * @see \Rekalogika\Analytics\Contracts\Model\Partition::getKey()
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class PartitionKey {}
