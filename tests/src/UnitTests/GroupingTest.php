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
    /**
     * @param list<string> $dimensions
     * @param list<string> $expectedGroupingFields
     * @param list<string> $expectedNonGroupingFields
     * @dataProvider groupingFieldProvider
     */
    public function testGroupingField(
        mixed $groupingField,
        array $dimensions,
        array $expectedGroupingFields,
        array $expectedNonGroupingFields,
        bool $error = false,
    ): void {
        if ($error) {
            $this->expectException(InvalidArgumentException::class);
        }

        $groupingField = new GroupingField(
            groupingField: $groupingField,
            dimensions: $dimensions,
        );

        $this->assertEquals(
            $expectedGroupingFields,
            $groupingField->getGroupingFields(),
        );

        $this->assertEquals(
            $expectedNonGroupingFields,
            $groupingField->getNonGroupingFields(),
        );
    }

    /**
     * Provides test cases for groupingField.
     *
     * @return iterable<string,array{groupingField:mixed,dimensions:list<string>,expectedGroupingFields:list<string>,expectedNonGroupingFields:list<string>,error?:bool}>
     */
    public static function groupingFieldProvider(): iterable
    {
        yield '2 dimensions' => [
            'groupingField' => '000011',
            'dimensions' => ['a', 'b', 'c', 'd', 'e', 'f'],
            'expectedGroupingFields' => ['e', 'f'],
            'expectedNonGroupingFields' => ['a', 'b', 'c', 'd'],
        ];

        yield '3 dimensions' => [
            'groupingField' => '000111',
            'dimensions' => ['a', 'b', 'c', 'd', 'e', 'f'],
            'expectedGroupingFields' => ['d', 'e', 'f'],
            'expectedNonGroupingFields' => ['a', 'b', 'c'],
        ];

        yield 'error' => [
            'groupingField' => '1',
            'dimensions' => ['a', 'b',],
            'expectedGroupingFields' => ['a'],
            'expectedNonGroupingFields' => ['b'],
            'error' => true,
        ];

        yield '1 grouping' => [
            'groupingField' => '1',
            'dimensions' => ['a'],
            'expectedGroupingFields' => ['a'],
            'expectedNonGroupingFields' => [],
        ];

        yield '1 nongrouping' => [
            'groupingField' => '0',
            'dimensions' => ['a'],
            'expectedGroupingFields' => [],
            'expectedNonGroupingFields' => ['a'],
        ];

        yield 'emptyerror' => [
            'groupingField' => '',
            'dimensions' => ['a'],
            'expectedGroupingFields' => [],
            'expectedNonGroupingFields' => [],
            'error' => true,
        ];

        yield 'empty' => [
            'groupingField' => '',
            'dimensions' => [],
            'expectedGroupingFields' => [],
            'expectedNonGroupingFields' => [],
        ];

        yield 'invalid input' => [
            'groupingField' => 12345, // invalid input
            'dimensions' => ['a', 'b', 'c', 'd', 'e', 'f'],
            'expectedGroupingFields' => [],
            'expectedNonGroupingFields' => [],
            'error' => true,
        ];

        yield 'invalid input string' => [
            'groupingField' => '12345', // invalid input
            'dimensions' => ['a', 'b', 'c', 'd', 'e', 'f'],
            'expectedGroupingFields' => [],
            'expectedNonGroupingFields' => [],
            'error' => true,
        ];

        yield 'mismatch' => [
            'groupingField' => '0000011',
            'dimensions' => ['a', 'b', 'c', 'd', 'e', 'f'],
            'expectedGroupingFields' => [],
            'expectedNonGroupingFields' => [],
            'error' => true,
        ];
    }
}
