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

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\NumberFormatStringifier;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @psalm-suppress MissingConstructor */
final class NumberFormatStringifierTest extends TestCase
{
    /**
     * @dataProvider provideFormatStringifier
     */
    public function testFormatStringifier(
        mixed $input,
        string $locale,
        string $expected,
    ): void {
        $translator = $this->createMock(TranslatorInterface::class);
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
    }
}
