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

namespace Rekalogika\Analytics\Tests\UnitTests\Numeric;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Numeric\Bin\IndexedBin;
use Rekalogika\Analytics\Numeric\Binning;
use Rekalogika\Analytics\Numeric\Binning\LinearBinning;
use Rekalogika\Analytics\Numeric\Binning\LogarithmicBinning;
use Rekalogika\Analytics\Numeric\Binning\ThresholdBinning;
use Rekalogika\Analytics\Numeric\RangeBin;
use Rekalogika\Analytics\Numeric\ValueResolver\RangeBinValueResolver;
use Rekalogika\Analytics\Tests\App\Entity\AgeGroup;
use Symfony\Component\Translation\IdentityTranslator;

final class NumericBinTest extends TestCase
{
    //
    // binnings
    //

    /**
     * @return iterable<string,array{Binning,string}>
     */
    public static function provideDQLExpression(): iterable
    {
        yield 'linear, unbounded' => [
            new LinearBinning(width: 10),
            'REKALOGIKA_FLOOR_DIV(x, 10)',
        ];

        yield 'linear, min and max' => [
            new LinearBinning(width: 10, min: 26, max: 66),
            'CASE WHEN x < 26 THEN -1 WHEN x >= 66 THEN 4 ELSE REKALOGIKA_FLOOR_DIV(x - 26, 10) END',
        ];

        yield 'linear, negative min, float width' => [
            new LinearBinning(width: 0.5, min: -2),
            'CASE WHEN x < -2 THEN -1 ELSE REKALOGIKA_FLOOR_DIV(x + 2, 0.5) END',
        ];

        yield 'linear, max only' => [
            new LinearBinning(width: 10, max: 100),
            'CASE WHEN x >= 100 THEN 10 ELSE REKALOGIKA_FLOOR_DIV(x, 10) END',
        ];

        yield 'logarithmic, defaults' => [
            new LogarithmicBinning(),
            'CASE WHEN x < 1 THEN -1 ELSE REKALOGIKA_FLOOR_LOG(x, 10, 1) END',
        ];

        yield 'logarithmic, max' => [
            new LogarithmicBinning(base: 2, min: 16, max: 128),
            'CASE WHEN x < 16 THEN -1 WHEN x >= 128 THEN 3 ELSE REKALOGIKA_FLOOR_LOG(x, 2, 16) END',
        ];

        yield 'threshold' => [
            new ThresholdBinning([31, 59, 109.5]),
            'CASE WHEN x < 31 THEN 0 WHEN x < 59 THEN 1 WHEN x < 109.5 THEN 2 WHEN x >= 109.5 THEN 3 ELSE REKALOGIKA_NULL() END',
        ];
    }

    #[DataProvider('provideDQLExpression')]
    public function testDQLExpression(Binning $binning, string $expected): void
    {
        $this->assertSame($expected, $binning->getDQLExpression('x'));
    }

    /**
     * @return iterable<string,array{Binning,?int,?int,array<int,array{int|float|null,int|float|null}>}>
     */
    public static function provideBounds(): iterable
    {
        yield 'linear, unbounded' => [
            new LinearBinning(width: 10),
            null,
            null,
            [
                -1 => [-10, 0],
                0 => [0, 10],
                5 => [50, 60],
            ],
        ];

        yield 'linear, min and max' => [
            new LinearBinning(width: 10, min: 26, max: 66),
            -1,
            4,
            [
                -1 => [null, 26],
                0 => [26, 36],
                3 => [56, 66],
                4 => [66, null],
            ],
        ];

        yield 'linear, max not aligned to the width' => [
            new LinearBinning(width: 10, min: 0, max: 25),
            -1,
            3,
            [
                1 => [10, 20],
                2 => [20, 25],
                3 => [25, null],
            ],
        ];

        yield 'linear, floating point noise' => [
            new LinearBinning(width: 0.1, min: 0, max: 0.3),
            -1,
            3,
            [
                2 => [0.2, 0.3],
                3 => [0.3, null],
            ],
        ];

        yield 'logarithmic, defaults' => [
            new LogarithmicBinning(),
            -1,
            null,
            [
                -1 => [null, 1],
                0 => [1, 10],
                3 => [1000, 10000],
            ],
        ];

        yield 'logarithmic, max is an exact power' => [
            new LogarithmicBinning(base: 10, min: 1, max: 1000),
            -1,
            3,
            [
                2 => [100, 1000],
                3 => [1000, null],
            ],
        ];

        yield 'logarithmic, max not a power' => [
            new LogarithmicBinning(base: 2, min: 16, max: 100),
            -1,
            3,
            [
                1 => [32, 64],
                2 => [64, 100],
                3 => [100, null],
            ],
        ];

        yield 'threshold' => [
            new ThresholdBinning([31, 59, 109]),
            0,
            3,
            [
                0 => [null, 31],
                1 => [31, 59],
                2 => [59, 109],
                3 => [109, null],
            ],
        ];
    }

    /**
     * @param array<int,array{int|float|null,int|float|null}> $bounds
     */
    #[DataProvider('provideBounds')]
    public function testBounds(
        Binning $binning,
        ?int $first,
        ?int $last,
        array $bounds,
    ): void {
        $this->assertSame($first, $binning->getFirstIndex());
        $this->assertSame($last, $binning->getLastIndex());

        foreach ($bounds as $index => [$lower, $upper]) {
            $bin = IndexedBin::create($binning, $index);

            if (\is_float($lower)) {
                $this->assertEqualsWithDelta($lower, $bin->getLowerBound(), 1e-9);
            } else {
                $this->assertSame($lower, $bin->getLowerBound(), "lower bound of bin $index");
            }

            if (\is_float($upper)) {
                $this->assertEqualsWithDelta($upper, $bin->getUpperBound(), 1e-9);
            } else {
                $this->assertSame($upper, $bin->getUpperBound(), "upper bound of bin $index");
            }
        }
    }

    /**
     * @return iterable<string,array{\Closure():mixed}>
     */
    public static function provideInvalidBinning(): iterable
    {
        yield 'linear, zero width' => [fn() => new LinearBinning(width: 0)];
        yield 'linear, infinite width' => [fn() => new LinearBinning(width: \INF)];
        yield 'linear, max not above min' => [fn() => new LinearBinning(width: 1, min: 5, max: 5)];
        yield 'logarithmic, base 1' => [fn() => new LogarithmicBinning(base: 1)];
        yield 'logarithmic, zero min' => [fn() => new LogarithmicBinning(min: 0)];
        yield 'logarithmic, max not above min' => [fn() => new LogarithmicBinning(min: 10, max: 10)];
        yield 'threshold, empty' => [fn() => new ThresholdBinning([])];
        yield 'threshold, not ascending' => [fn() => new ThresholdBinning([1, 3, 2])];
        yield 'threshold, duplicate' => [fn() => new ThresholdBinning([1, 1])];
        yield 'threshold, nan' => [fn() => new ThresholdBinning([1, \NAN])];
        yield 'index below the first' => [fn() => IndexedBin::create(new ThresholdBinning([1]), -1)];
        yield 'index above the last' => [fn() => IndexedBin::create(new LinearBinning(width: 1, max: 10), 11)];
    }

    /**
     * @param \Closure():mixed $factory
     */
    #[DataProvider('provideInvalidBinning')]
    public function testInvalidBinning(\Closure $factory): void
    {
        $this->expectException(InvalidArgumentException::class);
        $factory();
    }

    //
    // indexed bin
    //

    public function testIndexedBinIdentity(): void
    {
        $bin = IndexedBin::create(new LinearBinning(width: 10), 3);

        // an equal binning gives the same instance
        $this->assertSame($bin, IndexedBin::create(new LinearBinning(width: 10), 3));
        $this->assertNotSame($bin, IndexedBin::create(new LinearBinning(width: 5), 3));

        $this->assertSame($bin, $bin->getNext()?->getPrevious());
        $this->assertSame(3, $bin->getDatabaseValue());
    }

    public function testIndexedBinNavigation(): void
    {
        $binning = new LinearBinning(width: 10, min: 0, max: 30);
        $first = IndexedBin::create($binning, -1);
        $last = IndexedBin::create($binning, 3);

        $this->assertNull($first->getPrevious());
        $this->assertNull($last->getNext());

        $sequence = $first->getSequence();
        $this->assertNotNull($sequence);
        $this->assertSame($first, $sequence->getFirst());
        $this->assertSame($last, $sequence->getLast());

        $this->assertSame(
            [-1, 0, 1, 2, 3],
            array_map(
                static fn(IndexedBin $bin): int => $bin->getIndex(),
                iterator_to_array($sequence, false),
            ),
        );

        // unbounded above, so no known sequence
        $this->assertNull(IndexedBin::create(new LogarithmicBinning(), 0)->getSequence());
    }

    public function testIndexedBinCompare(): void
    {
        $binning = new LinearBinning(width: 10);

        $this->assertSame(-1, IndexedBin::compare(
            IndexedBin::create($binning, 1),
            IndexedBin::create($binning, 2),
        ));

        $this->assertSame(0, IndexedBin::compare(
            IndexedBin::create($binning, 2),
            IndexedBin::create(new LinearBinning(width: 10), 2),
        ));

        $this->expectException(InvalidArgumentException::class);

        IndexedBin::compare(
            IndexedBin::create($binning, 1),
            IndexedBin::create(new LinearBinning(width: 5), 1),
        );
    }

    /**
     * @return iterable<string,array{Binning,int,string}>
     */
    public static function provideLabel(): iterable
    {
        $linear = new LinearBinning(width: 10, min: 26, max: 66);

        yield 'underflow' => [$linear, -1, '< 26'];
        yield 'regular' => [$linear, 0, '[26, 36)'];
        yield 'overflow' => [$linear, 4, '≥ 66'];
        yield 'float' => [new LinearBinning(width: 0.1), 2, '[0.2, 0.3)'];
        yield 'float from integer' => [new LinearBinning(width: 2.5), 1, '[2.5, 5)'];
        yield 'logarithmic' => [new LogarithmicBinning(), 2, '[100, 1000)'];
    }

    #[DataProvider('provideLabel')]
    public function testLabel(Binning $binning, int $index, string $expected): void
    {
        $bin = IndexedBin::create($binning, $index);

        $this->assertSame($expected, (string) $bin);
        $this->assertSame($expected, $bin->trans(new IdentityTranslator()));
    }

    //
    // range bin
    //

    public function testRangeBinBounds(): void
    {
        $this->assertNull(AgeGroup::UpTo25->getLowerBound());
        $this->assertSame(26, AgeGroup::UpTo25->getUpperBound());
        $this->assertSame(26, AgeGroup::From26To35->getLowerBound());
        $this->assertSame(36, AgeGroup::From26To35->getUpperBound());
        $this->assertSame(66, AgeGroup::Over65->getLowerBound());
        $this->assertNull(AgeGroup::Over65->getUpperBound());

        $this->assertSame(2500.5, PriceBand::Moderate->getUpperBound());
    }

    private static function getNext(AgeGroup $ageGroup): ?AgeGroup
    {
        return $ageGroup->getNext();
    }

    private static function getPrevious(AgeGroup $ageGroup): ?AgeGroup
    {
        return $ageGroup->getPrevious();
    }

    public function testRangeBinNavigation(): void
    {
        $this->assertNull(self::getPrevious(AgeGroup::UpTo25));
        $this->assertSame(AgeGroup::From26To35, self::getNext(AgeGroup::UpTo25));
        $this->assertSame(AgeGroup::From56To65, self::getPrevious(AgeGroup::Over65));
        $this->assertNull(self::getNext(AgeGroup::Over65));

        $sequence = AgeGroup::From36To45->getSequence();
        $this->assertSame(AgeGroup::UpTo25, $sequence->getFirst());
        $this->assertSame(AgeGroup::Over65, $sequence->getLast());
        $this->assertSame(AgeGroup::cases(), iterator_to_array($sequence, false));
    }

    public function testRangeBinCompare(): void
    {
        // by the order of the cases, not by the alphabetical order of the
        // backing values
        $this->assertSame(-1, AgeGroup::compare(AgeGroup::UpTo25, AgeGroup::From26To35));
        $this->assertSame(-1, AgeGroup::compare(AgeGroup::UpTo25, AgeGroup::Over65));
        $this->assertSame(0, AgeGroup::compare(AgeGroup::Over65, AgeGroup::Over65));

        $this->expectException(InvalidArgumentException::class);
        AgeGroup::compare(AgeGroup::UpTo25, PriceBand::Cheap);
    }

    /**
     * @return iterable<string,array{class-string<RangeBin>}>
     */
    public static function provideInvalidRangeBin(): iterable
    {
        yield 'mismatched thresholds' => [MismatchedThresholdsBin::class];
        yield 'unsorted thresholds' => [UnsortedThresholdsBin::class];
    }

    /**
     * @param class-string<RangeBin> $class
     */
    #[DataProvider('provideInvalidRangeBin')]
    public function testInvalidRangeBin(string $class): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RangeBinValueResolver(
            binClass: $class,
            input: new PropertyValue('age'),
        );
    }
}
