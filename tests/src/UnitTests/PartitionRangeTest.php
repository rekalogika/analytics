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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Engine\Entity\PartitionRange;
use Rekalogika\Analytics\Tests\App\Entity\TestIntegerPartition;

final class PartitionRangeTest extends TestCase
{
    public function testSinglePartitionRange(): void
    {
        $partition = TestIntegerPartition::createFromSourceValue(4, 2);
        $range = new PartitionRange($partition, $partition);

        $this->assertCount(1, $range);
    }

    public function testStartGreaterThanEndIsRejected(): void
    {
        $start = TestIntegerPartition::createFromSourceValue(8, 2);
        $end = TestIntegerPartition::createFromSourceValue(4, 2);

        $this->expectException(InvalidArgumentException::class);

        new PartitionRange($start, $end);
    }
}
