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
use Rekalogika\Analytics\Bundle\Command\UuidConvertSummaryToSourceCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class UuidConvertSummaryToSourceCommandTest extends TestCase
{
    #[DataProvider('validKeyProvider')]
    public function testValidKey(string $key, string $expected): void
    {
        $tester = new CommandTester(new UuidConvertSummaryToSourceCommand());
        $status = $tester->execute(['integerkey' => $key]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame($expected, trim($tester->getDisplay()));
    }

    /**
     * @return iterable<string,array{key:string,expected:string}>
     */
    public static function validKeyProvider(): iterable
    {
        yield 'zero' => [
            'key' => '0',
            'expected' => '00000000-0000-0000-0000-000000000000',
        ];

        yield 'uuidv7' => [
            'key' => (string) 0x01943D05942A,
            'expected' => '01943d05-942a-0000-0000-000000000000',
        ];

        yield 'leading zeros' => [
            'key' => '000' . 0x01943D05942A,
            'expected' => '01943d05-942a-0000-0000-000000000000',
        ];

        yield 'top bit set' => [
            'key' => (string) 0xF192F0E45F5E,
            'expected' => 'f192f0e4-5f5e-0000-0000-000000000000',
        ];

        yield 'largest' => [
            'key' => (string) 0xFFFFFFFFFFFF,
            'expected' => 'ffffffff-ffff-0000-0000-000000000000',
        ];
    }

    #[DataProvider('invalidKeyProvider')]
    public function testInvalidKey(string $key): void
    {
        $tester = new CommandTester(new UuidConvertSummaryToSourceCommand());
        $status = $tester->execute(['integerkey' => $key]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString(
            'The integer key must be an integer between 0 and 281474976710655',
            $tester->getDisplay(),
        );
    }

    /**
     * @return iterable<string,array{key:string}>
     */
    public static function invalidKeyProvider(): iterable
    {
        yield 'empty' => ['key' => ''];
        yield 'not a number' => ['key' => 'foo'];
        yield 'fraction' => ['key' => '1.5'];
        yield 'exponent' => ['key' => '1e3'];
        yield 'negative' => ['key' => '-1'];
        yield 'hexadecimal' => ['key' => '0x10'];
        yield 'whitespace' => ['key' => ' 1'];
        yield 'too large' => ['key' => (string) (0xFFFFFFFFFFFF + 1)];
        yield 'overflow' => ['key' => '99999999999999999999'];
    }
}
