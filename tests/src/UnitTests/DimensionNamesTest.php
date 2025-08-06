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
use Rekalogika\Analytics\Engine\SummaryQuery\Output\Dimensionality;

final class DimensionNamesTest extends TestCase
{
    public function testBasic(): void
    {
        $dimensionNames = Dimensionality::create(['a', 'b', 'c']);

        $this->assertEquals(['a', 'b', 'c'], $dimensionNames->getDescendants());
        $this->assertEquals([], $dimensionNames->getAncestors());
        $this->assertNull($dimensionNames->getCurrent());
    }

    public function testDescendImmediate(): void
    {
        $dimensionNames = Dimensionality::create(['a', 'b', 'c']);
        $descended = $dimensionNames->descend('a');

        $this->assertEquals([], $descended->getAncestors());
        $this->assertEquals('a', $descended->getCurrent());
        $this->assertEquals(['b', 'c'], $descended->getDescendants());
    }

    public function testSingleDimensionDescend(): void
    {
        $dimensionNames = Dimensionality::create(['a']);
        $this->assertEquals(['a'], $dimensionNames->getDescendants());
        $this->assertEquals([], $dimensionNames->getAncestors());
        $this->assertNull($dimensionNames->getCurrent());

        $descended = $dimensionNames->descend('a');
        $this->assertEquals([], $descended->getAncestors());
        $this->assertEquals('a', $descended->getCurrent());
        $this->assertEquals([], $descended->getDescendants());
    }

    public function testDescendNonImmediate(): void
    {
        $dimensionNames = Dimensionality::create(['a', 'b', 'c']);
        $descended = $dimensionNames->descend('b');

        $this->assertEquals([], $descended->getAncestors());
        $this->assertEquals('b', $descended->getCurrent());
        $this->assertEquals(['c'], $descended->getDescendants());
    }

    public function testResolveName(): void
    {
        $dimensionNames = Dimensionality::create(['a', 'b', 'c']);

        $this->assertEquals('a', $dimensionNames->resolveName('a'));
        $this->assertEquals('b', $dimensionNames->resolveName('b'));
        $this->assertEquals('c', $dimensionNames->resolveName('c'));
        $this->assertEquals('a', $dimensionNames->resolveName(1));
        $this->assertEquals('b', $dimensionNames->resolveName(2));
    }
}
