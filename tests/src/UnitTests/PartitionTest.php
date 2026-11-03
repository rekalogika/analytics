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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Core\Partition\DefaultIntegerPartition;
use Rekalogika\Analytics\Engine\Entity\PartitionRange;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Uuid\Partition\UuidV7IntegerPartition;

final class PartitionTest extends TestCase
{
    #[DataProvider('partitionProvider')]
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

    /**
     * @return iterable<string,array{int}>
     */
    public static function levelProvider(): iterable
    {
        foreach (DefaultIntegerPartition::getAllLevels() as $level) {
            yield 'level ' . $level => [$level];
        }
    }

    #[DataProvider('levelProvider')]
    public function testLastPartition(int $level): void
    {
        $last = DefaultIntegerPartition::createFromSourceValue(PHP_INT_MAX, $level);

        $this->assertSame(PHP_INT_MAX, $last->getUpperBound());
        $this->assertNull($last->getNext());

        $previous = $last->getPrevious();
        $this->assertNotNull($previous);
        $this->assertSame($last->getLowerBound(), $previous->getUpperBound());

        $next = $previous->getNext();
        $this->assertNotNull($next);
        $this->assertSame($last->getKey(), $next->getKey());

        // iterating up to the last partition terminates

        $this->assertCount(2, new PartitionRange($previous, $last));
    }

    public function testLowerLevelRangeOfLastPartition(): void
    {
        $last = DefaultIntegerPartition::createFromSourceValue(PHP_INT_MAX, 55);
        $range = PartitionUtil::getLowerLevelPartitionRange($last);

        $this->assertNotNull($range);
        $this->assertSame($last->getLowerBound(), $range->getLowerBound());
        $this->assertSame(PHP_INT_MAX, $range->getUpperBound());
    }

    /**
     * @return iterable<string,array{mixed}>
     */
    public static function invalidSourceValueProvider(): iterable
    {
        yield 'fractional string' => ['1.5'];
        yield 'fractional float' => [4096.7];
        yield 'exponent string' => ['1e3'];
        yield 'non-numeric string' => ['foo'];
        yield 'empty string' => [''];
        yield 'out of range string' => ['9223372036854775808'];
        yield 'boolean' => [true];
        yield 'null' => [null];
    }

    #[DataProvider('invalidSourceValueProvider')]
    public function testInvalidSourceValue(mixed $source): void
    {
        $this->expectException(InvalidArgumentException::class);

        DefaultIntegerPartition::createFromSourceValue($source, 11);
    }

    /**
     * @return iterable<string,array{mixed}>
     */
    public static function validSourceValueProvider(): iterable
    {
        yield 'integer' => [4096];
        yield 'integer string' => ['4096'];
        yield 'integral float' => [4096.0];
    }

    #[DataProvider('validSourceValueProvider')]
    public function testValidSourceValue(mixed $source): void
    {
        $partition = DefaultIntegerPartition::createFromSourceValue($source, 11);

        $this->assertSame(4096, $partition->getKey());
    }
}
