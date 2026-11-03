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

namespace Rekalogika\Analytics\Tests\UnitTests\Ifier;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\NumberFormatStringifier;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @psalm-suppress MissingConstructor */
final class NumberFormatStringifierTest extends TestCase
{
    #[DataProvider('provideFormatStringifier')]
    public function testFormatStringifier(
        mixed $input,
        string $locale,
        string $expected,
    ): void {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('getLocale')->willReturn($locale);

        $stringifier = new NumberFormatStringifier($translator);

        $result = $stringifier->toString($input);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<array-key,array{mixed,string,string}>
     */
    public static function provideFormatStringifier(): iterable
    {
        yield [12345, 'id_ID', '12.345'];
        yield [12345.67, 'id_ID', '12.345,67'];
        yield [-12345.67, 'id_ID', '-12.345,67'];
        yield [-12345, 'id_ID', '-12.345'];

        // numbers with an absolute value of at least 1 keep 3 fraction digits
        yield [12345.6789, 'en', '12,345.679'];
        yield [12345.6789, 'id', '12.345,679'];
        yield [1.0005, 'en', '1'];
        yield [-3.33333, 'en', '-3.333'];
        yield [0, 'en', '0'];
        yield [0.0, 'en', '0'];
        yield [0.0, 'id', '0'];

        // fractions use 3 significant digits, small values are not shown as 0
        yield [0.000123, 'en', '0.000123'];
        yield [0.000123, 'id', '0,000123'];
        yield [-0.000123, 'en', '-0.000123'];
        yield [-0.000123, 'id', '-0,000123'];
        yield [0.12345, 'en', '0.123'];
        yield [0.12345, 'id', '0,123'];
        yield [0.5, 'en', '0.5'];
        yield [0.5, 'id', '0,5'];
        yield [-0.5, 'en', '-0.5'];
        yield [0.1 + 0.2, 'en', '0.3'];
        yield [0.9994, 'en', '0.999'];
        yield [0.9999, 'en', '1'];
        yield [-0.9999, 'id', '-1'];
    }

    public function testFormatterFollowsLocaleChange(): void
    {
        $locale = 'en';

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('getLocale')
            ->willReturnCallback(static function () use (&$locale): string {
                return $locale;
            });

        $stringifier = new NumberFormatStringifier($translator);

        $this->assertSame('1,234.5', $stringifier->toString(1234.5));
        $this->assertSame('0.0123', $stringifier->toString(0.0123));

        $locale = 'id';

        $this->assertSame('1.234,5', $stringifier->toString(1234.5));
        $this->assertSame('0,0123', $stringifier->toString(0.0123));
    }
}
