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
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\GroupingField;

final class GroupingTest extends TestCase
{
    public function testGroupingField(): void
    {
        $groupingField = new GroupingField(
            groupingField: '000011',
            dimensions: ['a', 'b', 'c', 'd', 'e', 'f'],
        );

        $this->assertEquals(
            ['e', 'f'],
            $groupingField->getGroupingFields(),
        );

        $this->assertEquals(
            ['a', 'b', 'c', 'd'],
            $groupingField->getNonGroupingFields(),
        );
    }

    public function testGroupingFieldOneField(): void
    {
        $groupingField = new GroupingField(
            groupingField: '1',
            dimensions: ['a',],
        );

        $this->assertEquals(
            ['a'],
            $groupingField->getGroupingFields(),
        );

        $this->assertEquals(
            [],
            $groupingField->getNonGroupingFields(),
        );
    }

    public function testGroupingFieldOneField2(): void
    {
        $groupingField = new GroupingField(
            groupingField: '0',
            dimensions: ['a'],
        );

        $this->assertEquals(
            [],
            $groupingField->getGroupingFields(),
        );

        $this->assertEquals(
            ['a'],
            $groupingField->getNonGroupingFields(),
        );
    }

    public function testInvalidInput1(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GroupingField(
            groupingField: 12345, // invalid input
            dimensions: ['a', 'b', 'c', 'd', 'e', 'f'],
        );
    }

    public function testInvalidInput2(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GroupingField(
            groupingField: '12345', // invalid input
            dimensions: ['a', 'b', 'c', 'd', 'e', 'f'],
        );
    }

    public function testMismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GroupingField(
            groupingField: '0000011',
            dimensions: ['a', 'b', 'c', 'd', 'e', 'f'],
        );
    }
}
