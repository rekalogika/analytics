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

namespace Rekalogika\Analytics\Model\Partition;

use Doctrine\ORM\Mapping\Embeddable;

/**
 * Single level partition, whole integer without truncating. Suitable for
 * partitioning snapshotted, historical data, where data in a different
 * partition cannot be summarized together.
 */
#[Embeddable]
final class SingleLevelIntegerPartition extends IntegerPartition
{
    #[\Override]
    public static function getAllLevels(): array
    {
        return [
            0,
        ];
    }
}
