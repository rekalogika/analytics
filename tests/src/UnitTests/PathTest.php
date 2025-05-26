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
use Rekalogika\Analytics\SimpleQueryBuilder\Internal\Path;

final class PathTest extends TestCase
{
    public function testBasic(): void
    {
        $path = new Path('a.b.c.d');
        $this->assertCount(4, $path);
        $this->assertSame('a', $path->getFirstPart()->getName());
        $this->assertSame('a', $path->getFullPathToFirst());
        $this->assertSame('a', $path->getFullPathToFirst(false));

        $path->shift();
        $this->assertCount(3, $path);
        $this->assertSame('b', $path->getFirstPart()->getName());
        $this->assertSame('a.b', $path->getFullPathToFirst());
        $this->assertSame('a.b', $path->getFullPathToFirst(false));

        $path->shift();
        $this->assertCount(2, $path);
        $this->assertSame('c', $path->getFirstPart()->getName());
        $this->assertSame('a.b.c', $path->getFullPathToFirst());
        $this->assertSame('a.b.c', $path->getFullPathToFirst(false));

        $path->shift();
        $this->assertCount(1, $path);
        $this->assertSame('d', $path->getFirstPart()->getName());
        $this->assertSame('a.b.c.d', $path->getFullPathToFirst());
        $this->assertSame('a.b.c.d', $path->getFullPathToFirst(false));

        $path->shift();
        $this->assertCount(0, $path);
        $this->expectException(UnexpectedValueException::class);
        $this->assertSame('d', $path->getFirstPart()->getName());
    }

    public function testAlias(): void
    {
        $path = new Path('*a');
        $this->assertCount(1, $path);
        $this->assertSame('a', $path->getFirstPart()->getName());
    }

}
