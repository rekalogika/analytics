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
 * Marks the property of a partition class that stores the partition level,
 * i.e. how large a range the partition covers. Used inside implementations of
 * the `Partition` contract, together with `PartitionKey`. The framework
 * indexes the summary table by these two columns.
 *
 * @see \Rekalogika\Analytics\Contracts\Model\Partition::getLevel()
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class PartitionLevel {}
