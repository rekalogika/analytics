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
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\SimpleQueryBuilder\Path\Path;

final class PathTest extends TestCase
{
    public function testBasic(): void
    {
        $path = Path::createFromString('a.b.c.d');
        $this->assertCount(4, $path);
        $this->assertSame('a', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(3, $path);
        $this->assertSame('b', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(2, $path);
        $this->assertSame('c', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(1, $path);
        $this->assertSame('d', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(0, $path);
        $this->expectException(UnexpectedValueException::class);
        $this->assertSame('d', $path->getFirstElement()->getName());
    }

    public function testAlias(): void
    {
        $path = Path::createFromString('a.*');
        $this->assertCount(2, $path);
        $this->assertSame('a', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(1, $path);
        $this->assertSame('*', $path->getFirstElement()->getName());
    }

    public function testAliasOfRelatedEntity(): void
    {
        $path = Path::createFromString('a.b.c.*');
        $this->assertCount(4, $path);
        $this->assertSame('a', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(3, $path);
        $this->assertSame('b', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(2, $path);
        $this->assertSame('c', $path->getFirstElement()->getName());

        $path->shift();
        $this->assertCount(1, $path);
        $this->assertSame('*', $path->getFirstElement()->getName());
    }

}
