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
use Rekalogika\Analytics\Uuid\Util\UuidV7Util;
use Symfony\Component\Uid\UuidV7;

final class UuidV7UtilTest extends TestCase
{
    #[DataProvider('integerProvider')]
    public function testNilAndMaxOfInteger(int $input, string $nil, string $max): void
    {
        $this->assertSame($nil, UuidV7Util::getNilOfInteger($input));
        $this->assertSame($max, UuidV7Util::getMaxOfInteger($input));
    }

    /**
     * @return iterable<string,array{input:int,nil:string,max:string}>
     */
    public static function integerProvider(): iterable
    {
        yield 'zero' => [
            'input' => 0,
            'nil' => '00000000-0000-0000-0000-000000000000',
            'max' => '00000000-0000-ffff-ffff-ffffffffffff',
        ];

        yield 'lower 16 bits are ignored' => [
            'input' => 0x0123456789ABFFFF,
            'nil' => '01234567-89ab-0000-0000-000000000000',
            'max' => '01234567-89ab-ffff-ffff-ffffffffffff',
        ];

        yield 'largest positive' => [
            'input' => \PHP_INT_MAX,
            'nil' => '7fffffff-ffff-0000-0000-000000000000',
            'max' => '7fffffff-ffff-ffff-ffff-ffffffffffff',
        ];

        yield 'only the top bit set' => [
            'input' => \PHP_INT_MIN,
            'nil' => '80000000-0000-0000-0000-000000000000',
            'max' => '80000000-0000-ffff-ffff-ffffffffffff',
        ];

        yield 'top bit set' => [
            'input' => 0xF192F0E45F5E << 16,
            'nil' => 'f192f0e4-5f5e-0000-0000-000000000000',
            'max' => 'f192f0e4-5f5e-ffff-ffff-ffffffffffff',
        ];

        yield 'all bits set' => [
            'input' => -1,
            'nil' => 'ffffffff-ffff-0000-0000-000000000000',
            'max' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
        ];
    }

    #[DataProvider('dateTimeProvider')]
    public function testNilAndMaxOfDateTime(string $dateTime, string $nil, string $max): void
    {
        $dateTime = new \DateTimeImmutable($dateTime);

        $this->assertSame($nil, UuidV7Util::getNilOfDateTime($dateTime));
        $this->assertSame($max, UuidV7Util::getMaxOfDateTime($dateTime));
    }

    /**
     * @return iterable<string,array{dateTime:string,nil:string,max:string}>
     */
    public static function dateTimeProvider(): iterable
    {
        yield 'epoch' => [
            'dateTime' => '1970-01-01T00:00:00Z',
            'nil' => '00000000-0000-0000-0000-000000000000',
            'max' => '00000000-0000-ffff-ffff-ffffffffffff',
        ];

        yield 'whole second' => [
            'dateTime' => '2025-01-01T00:00:00Z',
            'nil' => '01941f29-7c00-0000-0000-000000000000',
            'max' => '01941f29-7c00-ffff-ffff-ffffffffffff',
        ];

        yield 'milliseconds' => [
            'dateTime' => '2025-01-01T00:00:00.750Z',
            'nil' => '01941f29-7eee-0000-0000-000000000000',
            'max' => '01941f29-7eee-ffff-ffff-ffffffffffff',
        ];

        yield 'microseconds are truncated' => [
            'dateTime' => '2025-01-01T00:00:00.750999Z',
            'nil' => '01941f29-7eee-0000-0000-000000000000',
            'max' => '01941f29-7eee-ffff-ffff-ffffffffffff',
        ];

        yield 'other time zone' => [
            'dateTime' => '2025-01-01T07:00:00.750+07:00',
            'nil' => '01941f29-7eee-0000-0000-000000000000',
            'max' => '01941f29-7eee-ffff-ffff-ffffffffffff',
        ];

        yield 'latest' => [
            'dateTime' => '+10889-08-02T05:31:50.655Z',
            'nil' => 'ffffffff-ffff-0000-0000-000000000000',
            'max' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
        ];
    }

    public function testUuidV7IsBetweenNilAndMaxOfItsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable('2025-01-01T00:00:00.750Z');
        $uuid = UuidV7::generate($dateTime);

        $this->assertGreaterThanOrEqual(
            UuidV7Util::getNilOfDateTime($dateTime),
            $uuid,
        );

        $this->assertLessThanOrEqual(
            UuidV7Util::getMaxOfDateTime($dateTime),
            $uuid,
        );
    }

    #[DataProvider('outOfRangeDateTimeProvider')]
    public function testNilOfDateTimeOutOfRange(string $dateTime): void
    {
        $this->expectException(InvalidArgumentException::class);
        UuidV7Util::getNilOfDateTime(new \DateTimeImmutable($dateTime));
    }

    #[DataProvider('outOfRangeDateTimeProvider')]
    public function testMaxOfDateTimeOutOfRange(string $dateTime): void
    {
        $this->expectException(InvalidArgumentException::class);
        UuidV7Util::getMaxOfDateTime(new \DateTimeImmutable($dateTime));
    }

    /**
     * @return iterable<string,array{dateTime:string}>
     */
    public static function outOfRangeDateTimeProvider(): iterable
    {
        yield 'pre-epoch' => ['dateTime' => '1969-12-31T23:59:59Z'];
        yield 'one millisecond before epoch' => ['dateTime' => '1969-12-31T23:59:59.999Z'];
        yield 'one millisecond after the latest' => ['dateTime' => '+10889-08-02T05:31:50.656Z'];
        yield 'year 12000' => ['dateTime' => '+12000-01-01T00:00:00Z'];
    }
}
