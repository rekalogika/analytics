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
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Time\ValueResolver\DateToInteger;

final class DateToIntegerTest extends TestCase
{
    #[DataProvider('sourceValueProvider')]
    public function testSourceValueToSummaryValue(
        \DateTimeInterface|string $sourceValue,
        int $expected,
    ): void {
        $resolver = new DateToInteger('date');

        $this->assertSame(
            $expected,
            $resolver->transformSourceValueToSummaryValue($sourceValue),
        );
    }

    /**
     * @return iterable<string,array{sourceValue:\DateTimeInterface|string,expected:int}>
     */
    public static function sourceValueProvider(): iterable
    {
        yield 'default time zone' => [
            'sourceValue' => new \DateTimeImmutable('2024-01-01 00:00:00'),
            'expected' => 19723,
        ];

        yield 'string' => [
            'sourceValue' => '2024-01-01',
            'expected' => 19723,
        ];

        // the calendar date in the value's own time zone counts, not the
        // instant, whatever the default time zone is

        yield 'Asia/Jakarta' => [
            'sourceValue' => new \DateTimeImmutable('2024-01-01 00:00:00', new \DateTimeZone('Asia/Jakarta')),
            'expected' => 19723,
        ];

        yield 'America/New_York' => [
            'sourceValue' => new \DateTimeImmutable('2024-01-01 23:30:00', new \DateTimeZone('America/New_York')),
            'expected' => 19723,
        ];

        yield 'epoch in Asia/Jakarta' => [
            'sourceValue' => new \DateTimeImmutable('1970-01-01 00:00:00', new \DateTimeZone('Asia/Jakarta')),
            'expected' => 0,
        ];
    }

    public function testBeforeEpoch(): void
    {
        $resolver = new DateToInteger('date');

        $this->expectException(LogicException::class);
        $resolver->transformSourceValueToSummaryValue(
            new \DateTimeImmutable('1969-12-31 23:30:00', new \DateTimeZone('America/New_York')),
        );
    }

    public function testRoundTrip(): void
    {
        $resolver = new DateToInteger('date');

        $this->assertSame(
            '2024-01-01',
            $resolver->transformSummaryValueToSourceValue(19723)->format('Y-m-d'),
        );
    }
}
