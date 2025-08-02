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

namespace Rekalogika\Analytics\Tests\UnitTests\PartitionExpressionResolver;

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\PartitionExpressionResolver;

final class PartitionExpressionResolverTest extends TestCase
{
    /**
     * @dataProvider partitionExpressionProvider
     */
    public function testPartitionExpressionResolver(
        int $lastKey,
        string $expected,
    ): void {
        $resolver = new PartitionExpressionResolver(
            levelProperty: 'level',
            keyProperty: 'key',
            partitionClass: TestIntegerPartition::class,
        );

        $criteria = $resolver->resolvePartitionExpression($lastKey);
        $expression = $criteria->getWhereExpression();
        $this->assertNotNull($expression);

        $visitor = new SqlExpressionVisitor();
        $sql = $expression->visit($visitor);
        $this->assertIsString($sql);

        $this->assertEquals($expected, $sql);
    }

    /**
     * @return iterable<array-key,array{int,string}>
     */
    public static function partitionExpressionProvider(): iterable
    {
        yield 1 => [1, "(level = 1 AND key >= 0 AND key < 2)"];
        yield 2 => [2, "(level = 2 AND key >= 0 AND key < 4)"];
        yield 3 => [3, "(level = 2 AND key >= 0 AND key < 4)"];
        yield 4 => [4, "((level = 1 AND key >= 4 AND key < 6) OR (level = 2 AND key >= 0 AND key < 4))"];
        yield 5 => [5, "((level = 1 AND key >= 4 AND key < 6) OR (level = 2 AND key >= 0 AND key < 4))"];
        yield 6 => [6, "(level = 3 AND key >= 0 AND key < 8)"];
        yield 7 => [7, "(level = 3 AND key >= 0 AND key < 8)"];
        yield 8 => [8, "((level = 1 AND key >= 8 AND key < 10) OR (level = 3 AND key >= 0 AND key < 8))"];
        yield 9 => [9, "((level = 1 AND key >= 8 AND key < 10) OR (level = 3 AND key >= 0 AND key < 8))"];
        yield 10 => [10, "((level = 2 AND key >= 8 AND key < 12) OR (level = 3 AND key >= 0 AND key < 8))"];
        yield 11 => [11, "((level = 2 AND key >= 8 AND key < 12) OR (level = 3 AND key >= 0 AND key < 8))"];
        yield 12 => [12, "((level = 1 AND key >= 12 AND key < 14) OR (level = 2 AND key >= 8 AND key < 12) OR (level = 3 AND key >= 0 AND key < 8))"];
        yield 13 => [13, "((level = 1 AND key >= 12 AND key < 14) OR (level = 2 AND key >= 8 AND key < 12) OR (level = 3 AND key >= 0 AND key < 8))"];
        yield 14 => [14, "(level = 4 AND key >= 0 AND key < 16)"];
        yield 15 => [15, "(level = 4 AND key >= 0 AND key < 16)"];
    }
}
