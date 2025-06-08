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

namespace Rekalogika\Analytics\Tests\SimpleQueryBuilder;

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\SimpleQueryBuilder\Path\PathElement;
use Rekalogika\Analytics\Tests\App\Entity\Country;

final class PathElementTest extends TestCase
{
    public function testBasic(): void
    {
        $pathElement = PathElement::createFromString('foo');
        $this->assertSame('foo', $pathElement->getName());
        $this->assertNull($pathElement->getClassCast());
        $this->assertSame('foo', (string) $pathElement);
    }

    public function testCast(): void
    {
        $pathElement = PathElement::createFromString('foo(Rekalogika\Analytics\Tests\App\Entity\Country)');
        $this->assertSame('foo', $pathElement->getName());
        $this->assertSame(Country::class, $pathElement->getClassCast());
        $this->assertSame('foo(Rekalogika\Analytics\Tests\App\Entity\Country)', (string) $pathElement);
    }

    public function testRootAlias(): void
    {
        $pathElement = PathElement::createFromString('*');
        $this->assertNull($pathElement->getClassCast());
        $this->assertSame('*', (string) $pathElement);
    }

    public function testRootCast(): void
    {
        $pathElement = PathElement::createFromString('(Rekalogika\Analytics\Tests\App\Entity\Country)');
        $this->assertSame(Country::class, $pathElement->getClassCast());
        $this->assertSame('(Rekalogika\Analytics\Tests\App\Entity\Country)', (string) $pathElement);
    }

    public function testRootCastName(): void
    {
        $pathElement = PathElement::createFromString('(Rekalogika\Analytics\Tests\App\Entity\Country)');
        $this->assertEquals(Country::class, $pathElement->getClassCast());
        $this->assertSame('', $pathElement->getName());
    }
}
