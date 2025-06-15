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

namespace Rekalogika\Analytics\Uuid\Partition;

use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Model\Partition\IntegerPartition;

/**
 * Partition optimized for summarizing source entities with UUIDv7 primary key.
 */
#[Embeddable]
final class UuidV7IntegerPartition extends IntegerPartition
{
    #[\Override]
    public static function getAllLevels(): array
    {
        return [
            37, // 37 bits of ms = 4.3 years interval
            32, // 32 bits of ms = 50 days interval
            27, // 27 bits of ms = 1.6 days interval
            22, // 22 bits of ms = 1.165 hours interval
        ];
    }
}
