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
use Rekalogika\Analytics\Uuid\ValueResolver\StringUuidToTruncatedInteger;

final class UuidToTruncatedIntegerTest extends TestCase
{
    public function testTransform(): void
    {
        $resolver = new StringUuidToTruncatedInteger('foo');
        $uuid = '01943d05-942a-7e95-a9a1-bed59b37c877';

        $transformed = $resolver->transformSourceValueToSummaryValue($uuid);
        /** @psalm-suppress MixedAssignment */
        $reversed = $resolver->transformSummaryValueToSourceValue($transformed);

        $this->assertEquals('01943d05-942a-0000-0000-000000000000', $reversed);
    }

    public function testTransform2(): void
    {
        $resolver = new StringUuidToTruncatedInteger('foo');
        $uuid = '01963250-b507-74c8-8f78-f6bac13088ec';

        $transformed = $resolver->transformSourceValueToSummaryValue($uuid);
        /** @psalm-suppress MixedAssignment */
        $reversed = $resolver->transformSummaryValueToSourceValue($transformed);

        $this->assertEquals('01963250-b507-0000-0000-000000000000', $reversed);
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTrip(string $uuid, int $integer, string $reversed): void
    {
        $resolver = new StringUuidToTruncatedInteger('foo');

        $transformed = $resolver->transformSourceValueToSummaryValue($uuid);
        $this->assertSame($integer, $transformed);

        $this->assertSame(
            $reversed,
            $resolver->transformSummaryValueToSourceValue($transformed),
        );
    }

    /**
     * @return iterable<string,array{uuid:string,integer:int,reversed:string}>
     */
    public static function roundTripProvider(): iterable
    {
        yield 'nil' => [
            'uuid' => '00000000-0000-0000-0000-000000000000',
            'integer' => 0,
            'reversed' => '00000000-0000-0000-0000-000000000000',
        ];

        yield 'max' => [
            'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'integer' => 0xFFFFFFFFFFFF,
            'reversed' => 'ffffffff-ffff-0000-0000-000000000000',
        ];

        yield 'v4 with the top bit set' => [
            'uuid' => 'f192f0e4-5f5e-4c3a-9a6b-2f0d1e3c4b5a',
            'integer' => 0xF192F0E45F5E,
            'reversed' => 'f192f0e4-5f5e-0000-0000-000000000000',
        ];

        yield 'v4 with only the top bit set' => [
            'uuid' => '80000000-0000-4000-8000-000000000000',
            'integer' => 0x800000000000,
            'reversed' => '80000000-0000-0000-0000-000000000000',
        ];

        yield 'v4 without the top bit set' => [
            'uuid' => '7fffffff-ffff-4fff-bfff-ffffffffffff',
            'integer' => 0x7FFFFFFFFFFF,
            'reversed' => '7fffffff-ffff-0000-0000-000000000000',
        ];
    }
}
