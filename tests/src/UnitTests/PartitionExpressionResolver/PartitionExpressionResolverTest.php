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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Core\Partition\SingleLevelIntegerPartition;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\PartitionExpressionResolver;

final class PartitionExpressionResolverTest extends TestCase
{
    #[DataProvider('partitionExpressionProvider')]
    public function testPartitionExpressionResolver(
        int|string $lastKey,
        string $expected,
    ): void {
        $this->assertEquals(
            $expected,
            $this->resolveToSql(TestIntegerPartition::class, $lastKey),
        );
    }

    /**
     * @return iterable<array-key,array{int|string,string}>
     */
    public static function partitionExpressionProvider(): iterable
    {
        yield 0 => [0, "(level = 1 AND key >= 0 AND key < 2)"];
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

        // reaching the top level (6)
        yield 63 => [63, "(level = 6 AND key < 64)"];
        yield 64 => [64, "((level = 1 AND key >= 64 AND key < 66) OR (level = 6 AND key < 64))"];
        yield 100 => [100, "((level = 1 AND key >= 100 AND key < 102) OR (level = 2 AND key >= 96 AND key < 100) OR (level = 5 AND key >= 64 AND key < 96) OR (level = 6 AND key < 64))"];
        yield 127 => [127, "(level = 6 AND key < 128)"];

        // the database may return bigint as string
        yield 'numeric string' => ['12', "((level = 1 AND key >= 12 AND key < 14) OR (level = 2 AND key >= 8 AND key < 12) OR (level = 3 AND key >= 0 AND key < 8))"];
    }

    public function testSingleLevelPartition(): void
    {
        $this->assertEquals(
            "(level = 0 AND key < 6)",
            $this->resolveToSql(SingleLevelIntegerPartition::class, 5),
        );
    }

    public function testWidePartition(): void
    {
        // widths: L8 = 256, L5 = 32, L3 = 8, L1 = 2
        $this->assertEquals(
            "((level = 1 AND key >= 48 AND key < 52) OR (level = 3 AND key >= 32 AND key < 48) OR (level = 5 AND key >= 0 AND key < 32))",
            $this->resolveToSql(TestWideIntegerPartition::class, 50),
        );
    }

    /**
     * Evaluates the expression against a simulated summary table containing
     * every partition on every level (matched on the `level` and `key`
     * properties of the partition objects), and checks that the selected partitions
     * cover the range from zero up to the end of the last partition exactly
     * once.
     *
     * @param class-string<Partition> $partitionClass
     */
    #[DataProvider('partitionClassProvider')]
    public function testSelectedPartitionsCoverRangeExactlyOnce(
        string $partitionClass,
        int $maxValue,
    ): void {
        $levels = $partitionClass::getAllLevels();
        $lowestLevel = $levels[\count($levels) - 1];

        $rows = [];

        foreach ($levels as $level) {
            $partition = $partitionClass::createFromSourceValue(0, $level);

            while ($partition->getLowerBound() <= $maxValue) {
                $rows[] = $partition;

                $partition = $partition->getNext();
                $this->assertNotNull($partition);
            }
        }

        $summaryTable = new ArrayCollection($rows);

        $resolver = new PartitionExpressionResolver(
            levelProperty: 'level',
            keyProperty: 'key',
            partitionClass: $partitionClass,
        );

        for ($lastKey = 0; $lastKey <= $maxValue; $lastKey++) {
            $expectedEnd = $partitionClass::createFromSourceValue($lastKey, $lowestLevel)
                ->getUpperBound();

            $criteria = $resolver->resolvePartitionExpression($lastKey);

            $selected = array_values($summaryTable->matching($criteria)->toArray());

            usort(
                $selected,
                static fn(Partition $a, Partition $b): int => $a->getLowerBound() <=> $b->getLowerBound(),
            );

            // contiguous, non-overlapping, from zero to the expected end

            $position = 0;
            $usedLevels = [];

            foreach ($selected as $partition) {
                $this->assertSame(
                    $position,
                    $partition->getLowerBound(),
                    \sprintf('Gap or overlap at %d, last key %d', $position, $lastKey),
                );

                $position = $partition->getUpperBound();
                $usedLevels[$partition->getLevel()] = true;
            }

            $this->assertSame($expectedEnd, $position, \sprintf('Last key %d', $lastKey));

            // each level should contribute at most one OR term

            $whereExpression = $criteria->getWhereExpression();
            $this->assertNotNull($whereExpression);
            $sql = $whereExpression->visit(new SqlExpressionVisitor());
            $this->assertIsString($sql);
            $this->assertSame(
                \count($usedLevels),
                substr_count($sql, 'level ='),
                \sprintf('Last key %d', $lastKey),
            );
        }
    }

    /**
     * @return iterable<string,array{class-string<Partition>,int}>
     */
    public static function partitionClassProvider(): iterable
    {
        // fan-out of 2 on every level, the top level is reached at 64
        yield 'binary' => [TestIntegerPartition::class, 300];

        // mixed fan-outs, the top level is reached at 256
        yield 'wide' => [TestWideIntegerPartition::class, 700];
    }

    /**
     * @param class-string<Partition> $partitionClass
     */
    private function resolveToSql(string $partitionClass, int|string $lastKey): string
    {
        $resolver = new PartitionExpressionResolver(
            levelProperty: 'level',
            keyProperty: 'key',
            partitionClass: $partitionClass,
        );

        $criteria = $resolver->resolvePartitionExpression($lastKey);
        $expression = $criteria->getWhereExpression();
        $this->assertNotNull($expression);

        $visitor = new SqlExpressionVisitor();
        $sql = $expression->visit($visitor);
        $this->assertIsString($sql);

        return $sql;
    }
}
