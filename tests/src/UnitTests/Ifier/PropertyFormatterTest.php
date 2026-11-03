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
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainHtmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\DefaultStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\NumberFormatStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Property\PropertyHtmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Property\PropertyStringifier;
use Rekalogika\Analytics\PivotTable\Model\Property;
use Rekalogika\Analytics\PivotTable\Model\Table\TableMember;
use Rekalogika\Analytics\PivotTable\Model\Table\TableValue;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PropertyFormatterTest extends TestCase
{
    /**
     * @return iterable<string,array{Property,string}>
     */
    public static function provideProperty(): iterable
    {
        yield 'integer member' => [new TableMember(2024), '2024'];
        yield 'integer value' => [new TableValue(2024, 'count'), '2,024'];
        yield 'float value' => [new TableValue(2024.5, 'price'), '2,024.5'];
    }

    #[DataProvider('provideProperty')]
    public function testStringifier(Property $property, string $expected): void
    {
        $this->assertSame($expected, $this->createStringifier()->toString($property));
    }

    #[DataProvider('provideProperty')]
    public function testHtmlifier(Property $property, string $expected): void
    {
        $htmlifier = new ChainHtmlifier(
            htmlifiers: [new PropertyHtmlifier()],
            stringifier: $this->createStringifier(),
        );

        $this->assertSame($expected, $htmlifier->toHtml($property));
    }

    private function createStringifier(): ChainStringifier
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('getLocale')->willReturn('en_US');

        // in the order of the priorities in the bundle configuration
        return new ChainStringifier([
            new NumberFormatStringifier($translator),
            new PropertyStringifier(),
            new DefaultStringifier(),
        ]);
    }
}
