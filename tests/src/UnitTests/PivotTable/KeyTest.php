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

namespace Rekalogika\Analytics\Tests\UnitTests\PivotTable;

use PHPUnit\Framework\TestCase;
use Rekalogika\PivotTable\Block\Keys;

final class KeyTest extends TestCase
{
    public function testGetNextKey(): void
    {
        $keys = new Keys(
            unpivotedKeys: ['a', 'b', 'c'],
            pivotedKeys: ['d', 'e'],
            currentKeyPath: ['a', 'b'],
        );

        $this->assertSame('c', $keys->getNextKey(1));
        $this->assertSame('d', $keys->getNextKey(2));
        $this->assertSame('e', $keys->getNextKey(3));
        $this->assertNull($keys->getNextKey(4));
    }

    public function testGetNextKeyWithEmptyKeys(): void
    {
        $keys = new Keys(
            unpivotedKeys: [],
            pivotedKeys: [],
            currentKeyPath: [],
        );

        $this->assertNull($keys->getNextKey(1));
    }

    public function testGetNextKeyWithSingleKey(): void
    {
        $keys = new Keys(
            unpivotedKeys: ['a'],
            pivotedKeys: [],
            currentKeyPath: ['a'],
        );

        $this->assertNull($keys->getNextKey(1));
    }

    public function testGetNextKeyWithSinglePivotedKey(): void
    {
        $keys = new Keys(
            unpivotedKeys: [],
            pivotedKeys: ['a'],
            currentKeyPath: ['a'],
        );

        $this->assertNull($keys->getNextKey(1));
    }

    public function testGetNextKeyWithUnpivotedAndPivotedKeys(): void
    {
        $keys = new Keys(
            unpivotedKeys: ['a', 'b'],
            pivotedKeys: ['c', 'd'],
            currentKeyPath: ['a', 'b'],
        );

        $this->assertSame('c', $keys->getNextKey(1));
        $this->assertSame('d', $keys->getNextKey(2));
        $this->assertNull($keys->getNextKey(3));
    }

    public function testCurrentKeyPath(): void
    {
        $keys = new Keys(
            unpivotedKeys: ['a', 'b'],
            pivotedKeys: ['c', 'd'],
            currentKeyPath: ['a', 'b'],
        );

        $this->assertSame(['a', 'b'], $keys->getCurrentKeyPath());
        $this->assertSame('b', $keys->getCurrentKey());
    }

    public function testGetKeys(): void
    {
        $keys = new Keys(
            unpivotedKeys: ['a', 'b'],
            pivotedKeys: ['c', 'd'],
            currentKeyPath: ['a', 'b'],
        );

        $this->assertSame(['a', 'b', 'c', 'd'], $keys->getKeys());
        $this->assertSame(['a', 'b'], $keys->getUnpivotedKeys());
        $this->assertSame(['c', 'd'], $keys->getPivotedKeys());
    }

    public function testCurrentKeyPathEmpty(): void
    {
        $keys = new Keys(
            unpivotedKeys: [],
            pivotedKeys: [],
            currentKeyPath: [],
        );

        $this->assertSame([], $keys->getCurrentKeyPath());
        $this->assertNull($keys->getCurrentKey());
    }
}
