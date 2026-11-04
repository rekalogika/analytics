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

namespace Rekalogika\Analytics\Tests\IntegrationTests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Core\ValueResolver\CustomExpression;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Numeric\Bin\IndexedBin;
use Rekalogika\Analytics\Numeric\Binning;
use Rekalogika\Analytics\Numeric\Binning\LinearBinning;
use Rekalogika\Analytics\Numeric\Binning\LogarithmicBinning;
use Rekalogika\Analytics\Numeric\Binning\ThresholdBinning;
use Rekalogika\Analytics\Numeric\NumericBin;
use Rekalogika\Analytics\Numeric\ValueResolver\NumericBinValueResolver;
use Rekalogika\Analytics\Numeric\ValueResolver\RangeBinValueResolver;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\Analytics\Tests\App\Entity\AgeGroup;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\UnitTests\Numeric\PriceBand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class NumericBinTest extends KernelTestCase
{
    /**
     * @return list<int>
     */
    private function getAges(): array
    {
        $ages = [];

        $customers = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(IndividualCustomer::class)
            ->findAll();

        foreach ($customers as $customer) {
            $age = $customer->getAge();
            $this->assertNotNull($age);
            $ages[] = $age;
        }

        $this->assertNotEmpty($ages);

        return $ages;
    }

    private static function isInBin(int|float $value, NumericBin $bin): bool
    {
        $lower = $bin->getLowerBound();
        $upper = $bin->getUpperBound();

        return ($lower === null || $value >= $lower)
            && ($upper === null || $value < $upper);
    }

    /**
     * Drills down the customer summary by the dimension, and returns the
     * members with their counts, in the order of the result.
     *
     * @return list<array{mixed,int}>
     */
    private function getMembersAndCounts(
        string $dimension,
        ?Expression $dice = null,
    ): array {
        $result = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(CustomerSummary::class)
            ->withDimensions($dimension)
            ->dice($dice)
            ->getResult();

        $output = [];

        foreach ($result->drillDown($dimension) as $cell) {
            /** @psalm-suppress MixedAssignment */
            $member = $cell->getCoordinates()->get($dimension)?->getMember();
            $count = $cell->getMeasures()->get('count')?->getValue() ?? 0;
            $this->assertIsInt($count);

            $output[] = [$member, $count];
        }

        return $output;
    }

    public function testRangeBinDimension(): void
    {
        $ages = $this->getAges();
        $membersAndCounts = $this->getMembersAndCounts('ageGroup');

        // every case in the order of the cases, then the organizational
        // customers, who have no age
        $this->assertSame(
            [...AgeGroup::cases(), null],
            array_column($membersAndCounts, 0),
        );

        foreach ($membersAndCounts as [$member, $count]) {
            if ($member === null) {
                continue;
            }

            $this->assertInstanceOf(AgeGroup::class, $member);

            $expected = \count(array_filter(
                $ages,
                static fn(int $age): bool => self::isInBin($age, $member),
            ));

            $this->assertSame($expected, $count, $member->name);
        }
    }

    public function testRangeBinGapFilling(): void
    {
        $membersAndCounts = $this->getMembersAndCounts(
            'ageGroup',
            Criteria::expr()->in('ageGroup', [AgeGroup::UpTo25, AgeGroup::Over65]),
        );

        // the sequence of a range bin is bounded, so all the cases are
        // returned
        $this->assertSame(AgeGroup::cases(), array_column($membersAndCounts, 0));

        /** @psalm-suppress MixedAssignment */
        foreach ($membersAndCounts as [$member, $count]) {
            if ($member !== AgeGroup::UpTo25 && $member !== AgeGroup::Over65) {
                $this->assertSame(0, $count);
            } else {
                $this->assertGreaterThan(0, $count);
            }
        }
    }

    /**
     * @return iterable<string,array{string,Binning,list<string>|null}>
     */
    public static function provideNumericBinDimension(): iterable
    {
        yield 'linear' => [
            'ageLinear',
            new LinearBinning(width: 10, min: 20, max: 70),
            ['< 20', '[20, 30)', '[30, 40)', '[40, 50)', '[50, 60)', '[60, 70)', '≥ 70'],
        ];

        yield 'logarithmic' => [
            'ageLogarithmic',
            new LogarithmicBinning(base: 2, min: 16),
            null,
        ];
    }

    /**
     * @param list<string>|null $expectedLabels
     */
    #[DataProvider('provideNumericBinDimension')]
    public function testNumericBinDimension(
        string $dimension,
        Binning $binning,
        ?array $expectedLabels,
    ): void {
        $ages = $this->getAges();
        $membersAndCounts = $this->getMembersAndCounts($dimension);

        // the organizational customers, who have no age
        [$nullMember] = array_pop($membersAndCounts) ?? [false];
        $this->assertNull($nullMember);

        $total = 0;
        $previous = null;
        $labels = [];

        foreach ($membersAndCounts as [$member, $count]) {
            $this->assertInstanceOf(IndexedBin::class, $member);
            $this->assertEquals($binning, $member->getBinning());

            // a gapless sequence
            if ($previous !== null) {
                $this->assertSame($previous->getNext(), $member);
            }

            $expected = \count(array_filter(
                $ages,
                static fn(int $age): bool => self::isInBin($age, $member),
            ));

            $this->assertSame($expected, $count, (string) $member);

            $total += $count;
            $previous = $member;
            $labels[] = (string) $member;
        }

        $this->assertSame(\count($ages), $total);

        if ($expectedLabels !== null) {
            $this->assertSame($expectedLabels, $labels);
        }
    }

    public function testDiceWithIndexedBin(): void
    {
        $bin = IndexedBin::create(new LinearBinning(width: 10, min: 20, max: 70), 2);

        $membersAndCounts = $this->getMembersAndCounts(
            'ageLinear',
            Criteria::expr()->eq('ageLinear', $bin),
        );

        $expected = \count(array_filter(
            $this->getAges(),
            static fn(int $age): bool => self::isInBin($age, $bin),
        ));

        $this->assertGreaterThan(0, $expected);

        // the binning is bounded, so the gaps are filled with all the bins
        $this->assertCount(7, $membersAndCounts);

        /** @psalm-suppress MixedAssignment */
        foreach ($membersAndCounts as [$member, $count]) {
            $this->assertSame($member === $bin ? $expected : 0, $count);
        }
    }

    /**
     * Inputs spanning negative numbers, decimals, and several orders of
     * magnitude. The prices are 100 to 10000.
     *
     * @return iterable<string,array{string,Binning}>
     */
    public static function provideBinningAgainstDatabase(): iterable
    {
        $inputs = [
            'integer' => '[price]',
            'negative' => '[price] - 5000',
            'decimal' => '[price] / 7.0',
        ];

        $binnings = [
            'linear' => new LinearBinning(width: 1000),
            'linear, min and max' => new LinearBinning(width: 750, min: 1000, max: 8000),
            'linear, negative min' => new LinearBinning(width: 333, min: -2000),
            'linear, decimal width' => new LinearBinning(width: 12.5, max: 1000),
            'logarithmic' => new LogarithmicBinning(),
            'logarithmic, decimal base' => new LogarithmicBinning(base: 1.5, min: 50, max: 5000),
            'threshold' => new ThresholdBinning([0, 150.5, 1000, 3333]),
        ];

        foreach ($inputs as $inputName => $input) {
            foreach ($binnings as $binningName => $binning) {
                yield \sprintf('%s, %s', $binningName, $inputName) => [$input, $binning];
            }
        }
    }

    #[DataProvider('provideBinningAgainstDatabase')]
    public function testBinningAgainstDatabase(string $input, Binning $binning): void
    {
        $rows = $this->select(
            new NumericBinValueResolver(
                binning: $binning,
                input: new CustomExpression($input),
            ),
            new CustomExpression($input),
        );

        foreach ($rows as [$bin, $value]) {
            $this->assertIsNumeric($bin);
            $this->assertIsNumeric($value);

            $bin = IndexedBin::create($binning, (int) $bin);

            $this->assertTrue(
                self::isInBin((float) $value, $bin),
                \sprintf('%s is not in %s', $value, (string) $bin),
            );
        }
    }

    public function testRangeBinAgainstDatabase(): void
    {
        $rows = $this->select(
            new RangeBinValueResolver(
                binClass: PriceBand::class,
                input: new CustomExpression('[price]'),
            ),
            new CustomExpression('[price]'),
        );

        $seen = [];

        foreach ($rows as [$band, $value]) {
            $this->assertIsNumeric($band);
            $this->assertIsNumeric($value);

            $band = PriceBand::from((int) $band);
            $seen[$band->name] = true;

            $this->assertTrue(
                self::isInBin((float) $value, $band),
                \sprintf('%s is not in %s', $value, $band->name),
            );
        }

        $this->assertCount(\count(PriceBand::cases()), $seen);
    }

    /**
     * Selects the expressions of the value resolvers from the items.
     *
     * @return list<array{mixed,mixed}>
     */
    private function select(ValueResolver $a, ValueResolver $b): array
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $summaryMetadata = static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata(OrderSummary::class);

        $queryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: Item::class,
            alias: 'root',
        );

        $context = new SourceQueryContext(
            queryBuilder: $queryBuilder,
            summaryMetadata: $summaryMetadata,
        );

        $rows = $queryBuilder
            ->select(\sprintf('%s AS a', $a->getExpression($context)))
            ->addSelect(\sprintf('%s AS b', $b->getExpression($context)))
            ->getQuery()
            ->getArrayResult();

        $this->assertNotEmpty($rows);

        $output = [];

        foreach ($rows as $row) {
            $this->assertIsArray($row);
            $output[] = [$row['a'] ?? null, $row['b'] ?? null];
        }

        return $output;
    }
}
