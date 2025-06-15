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
use Rekalogika\Analytics\Uuid\Partition\UuidV7IntegerPartition;

final class PartitionTest extends TestCase
{
    /**
     * @dataProvider partitionProvider
     */
    public function testPartition(
        int $sourceValue,
        int $level,
        int $expectedKey,
        int $expectedUpperBound,
    ): void {
        $partition = UuidV7IntegerPartition::createFromSourceValue($sourceValue, $level);
        $key = $partition->getKey();

        $upperBound = $partition->getUpperBound();

        $this->assertEquals($expectedKey, $key);
        $this->assertEquals($expectedUpperBound, $upperBound);
    }

    /**
     * @return iterable<array-key,array{int,int,int,int}>
     */
    public static function partitionProvider(): iterable
    {
        // note: php integers are signed

        yield [
            0b0101010_10101010_10101010_10101010_10101010_10101010_10101010_10101010,
            22,
            0b0101010_10101010_10101010_10101010_10101010_10000000_00000000_00000000,
            0b0101010_10101010_10101010_10101010_10101010_10111111_11111111_11111111 + 1,
        ];

        yield [
            0b1010101_01010101_01010101_01010101_01010101_01010101_01010101_01010101,
            22,
            0b1010101_01010101_01010101_01010101_01010101_01000000_00000000_00000000,
            0b1010101_01010101_01010101_01010101_01010101_01111111_11111111_11111111 + 1,
        ];
    }

    public function testNegativeInstantiation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UuidV7IntegerPartition::createFromSourceValue(123, 23);
    }
}
