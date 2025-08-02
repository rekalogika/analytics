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

namespace Rekalogika\Analytics\Tests\UnitTests\PartitionExpressionResolver;

use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Core\Partition\IntegerPartition;

#[Embeddable]
final class TestIntegerPartition extends IntegerPartition
{
    #[\Override]
    public static function getAllLevels(): array
    {
        return [
            6,
            5,
            4,
            3,
            2,
            1,
        ];
    }
}
