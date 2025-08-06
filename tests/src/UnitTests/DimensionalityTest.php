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

final class DimensionalityTest extends TestCase
{
    public function testBasic(): void
    {
        $dimensionality = Dimensionality::create(['a', 'b', 'c']);

        $this->assertEquals(['a', 'b', 'c'], $dimensionality->getDescendants());
        $this->assertEquals([], $dimensionality->getAncestors());
        $this->assertNull($dimensionality->getCurrent());
    }

    public function testDescendImmediate(): void
    {
        $dimensionality = Dimensionality::create(['a', 'b', 'c']);
        $descended = $dimensionality->descend('a');

        $this->assertEquals([], $descended->getAncestors());
        $this->assertEquals('a', $descended->getCurrent());
        $this->assertEquals(['b', 'c'], $descended->getDescendants());
    }

    public function testSingleDimensionDescend(): void
    {
        $dimensionality = Dimensionality::create(['a']);
        $this->assertEquals(['a'], $dimensionality->getDescendants());
        $this->assertEquals([], $dimensionality->getAncestors());
        $this->assertNull($dimensionality->getCurrent());

        $descended = $dimensionality->descend('a');
        $this->assertEquals([], $descended->getAncestors());
        $this->assertEquals('a', $descended->getCurrent());
        $this->assertEquals([], $descended->getDescendants());
    }

    public function testDescendNonImmediate(): void
    {
        $dimensionality = Dimensionality::create(['a', 'b', 'c']);
        $descended = $dimensionality->descend('b');

        $this->assertEquals([], $descended->getAncestors());
        $this->assertEquals('b', $descended->getCurrent());
        $this->assertEquals(['c'], $descended->getDescendants());
    }

    public function testResolveName(): void
    {
        $dimensionality = Dimensionality::create(['a', 'b', 'c']);

        $this->assertEquals('a', $dimensionality->resolveName('a'));
        $this->assertEquals('b', $dimensionality->resolveName('b'));
        $this->assertEquals('c', $dimensionality->resolveName('c'));
        $this->assertEquals('a', $dimensionality->resolveName(1));
        $this->assertEquals('b', $dimensionality->resolveName(2));
    }
}
