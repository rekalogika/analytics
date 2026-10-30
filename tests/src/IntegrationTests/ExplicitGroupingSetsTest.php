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
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\Exception\GroupingSetNotAvailable;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Core\GroupingStrategy\ExplicitGroupingSetsStrategy;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Category;
use Rekalogika\Analytics\Tests\App\Entity\OrderExplicitGroupingSetsSummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * OrderExplicitGroupingSetsSummary has the same source and dimensions as
 * OrderSummary, but stores only explicit grouping sets. Its query results
 * must be the same as OrderSummary's whenever the query can be answered.
 */
final class ExplicitGroupingSetsTest extends KernelTestCase
{
    private function getMetadata(): SummaryMetadata
    {
        return static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata(OrderExplicitGroupingSetsSummary::class);
    }

    /**
     * @param class-string $class
     */
    private function getQuery(string $class): Query
    {
        return static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from($class);
    }

    /**
     * @return list<list<string>> stored grouping sets as sorted dimension
     * names
     */
    private function getStoredSets(SummaryMetadata $metadata): array
    {
        $flattened = $metadata->getGroupByExpression()->flatten();
        $groupingSet = iterator_to_array($flattened, false)[0] ?? null;
        $this->assertInstanceOf(GroupingSet::class, $groupingSet);

        $sets = [];

        foreach ($groupingSet as $fieldSet) {
            $this->assertInstanceOf(FieldSet::class, $fieldSet);

            $names = array_map(
                static fn(Field $field): string => $metadata
                    ->getDimensionByAlias($field->getContent())
                    ->getName(),
                $fieldSet->toArray(),
            );

            sort($names);
            $sets[] = $names;
        }

        return $sets;
    }

    //
    // metadata
    //

    public function testStoredGroupingSets(): void
    {
        $metadata = $this->getMetadata();
        $sets = $this->getStoredSets($metadata);

        // the alternatives of the time dimension group
        $time = $metadata->getRootDimension('time')->getGroupByExpression();
        $this->assertInstanceOf(GroupingSet::class, $time);
        $timeAlternatives = \count($time->flatten());

        // the first set has one stored set for each alternative of the time
        // dimension group, the other two sets are one each
        $this->assertCount($timeAlternatives + 2, $sets);
        $this->assertContains(['customerRegion'], $sets);
        $this->assertContains(['customerGender', 'customerType'], $sets);

        foreach ($sets as $set) {
            if ($set === ['customerRegion'] || $set === ['customerGender', 'customerType']) {
                continue;
            }

            $nonTime = array_values(array_filter(
                $set,
                static fn(string $name): bool => !str_starts_with($name, 'time.'),
            ));

            $this->assertSame(
                ['customerCountry', 'customerType', 'itemCategory'],
                $nonTime,
            );
        }
    }

    public function testUnknownDimensionFailsAtMetadataBuild(): void
    {
        $metadata = $this->getMetadata();

        $this->expectException(MetadataException::class);
        $this->expectExceptionMessageMatches('/Unknown dimension "foo"/');

        new SummaryMetadata(
            sourceClass: $metadata->getSourceClass(),
            summaryClass: $metadata->getSummaryClass(),
            partition: $metadata->getPartition(),
            dimensions: $metadata->getRootDimensions(),
            measures: $metadata->getMeasures(),
            groupingsProperty: $metadata->getGroupingsProperty(),
            attributes: $metadata->getAttributes(),
            label: $metadata->getLabel(),
            groupingStrategy: new ExplicitGroupingSetsStrategy([
                ['customerCountry', 'foo'],
            ]),
        );
    }

    //
    // refresh
    //

    public function testRefreshStoresOnlyTheExplicitSets(): void
    {
        $metadata = $this->getMetadata();
        $leaves = array_keys($metadata->getLeafDimensions());
        sort($leaves);

        // the expected values of the groupings column, one for each stored set

        $expected = [];

        foreach ($this->getStoredSets($metadata) as $set) {
            $string = '';

            foreach ($leaves as $leaf) {
                $string .= \in_array($leaf, $set, true) ? '0' : '1';
            }

            $expected[] = $string;
        }

        // the actual values in the summary table

        /** @var list<array{groupings:string}> */
        $rows = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->createQueryBuilder()
            ->select(\sprintf('DISTINCT s.%s AS groupings', $metadata->getGroupingsProperty()))
            ->from(OrderExplicitGroupingSetsSummary::class, 's')
            ->getQuery()
            ->getArrayResult();

        $actual = array_column($rows, 'groupings');

        $this->assertNotEmpty($actual, 'The summary table is empty. Run "make summary-refresh" first.');

        foreach ($actual as $groupings) {
            $this->assertContains($groupings, $expected);
        }

        // the three sets that do not involve time must always be present
        $this->assertGreaterThanOrEqual(3, \count($actual));
    }

    //
    // query
    //

    /**
     * @return iterable<string,array{list<string>}>
     */
    public static function provideDimensions(): iterable
    {
        yield 'no dimension' => [[]];
        yield 'one dimension of the fine set' => [['customerCountry']];
        yield 'dimension in two sets' => [['customerType']];
        yield 'single dimension set' => [['customerRegion']];
        yield 'small set' => [['customerGender']];
        yield 'two dimensions' => [['customerType', 'itemCategory']];
        yield 'time and dimension' => [['time.civil.year', 'customerCountry']];
        yield 'time hierarchy' => [['time.civil.month.month', 'itemCategory']];
        yield 'iso week' => [['time.isoWeek.weekYear', 'customerType']];
    }

    /**
     * @param list<string> $dimensions
     */
    #[DataProvider('provideDimensions')]
    public function testQueryReturnsTheSameAsCube(array $dimensions): void
    {
        $expected = $this->collect(
            $this->getQuery(OrderSummary::class)
                ->withDimensions(...$dimensions)
                ->getResult(),
            $dimensions,
        );

        $actual = $this->collect(
            $this->getQuery(OrderExplicitGroupingSetsSummary::class)
                ->withDimensions(...$dimensions)
                ->getResult(),
            $dimensions,
        );

        $this->assertNotEmpty($expected);
        $this->assertEquals($expected, $actual);
    }

    public function testDiceOnDimensionNotInQuery(): void
    {
        $category = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Category::class)
            ->findOneBy([]);

        $this->assertNotNull($category);

        $dimensions = ['customerType'];
        $dice = Criteria::expr()->eq('itemCategory', $category);

        $expected = $this->collect(
            $this->getQuery(OrderSummary::class)
                ->withDimensions(...$dimensions)
                ->dice($dice)
                ->getResult(),
            $dimensions,
        );

        $actual = $this->collect(
            $this->getQuery(OrderExplicitGroupingSetsSummary::class)
                ->withDimensions(...$dimensions)
                ->dice($dice)
                ->getResult(),
            $dimensions,
        );

        $this->assertNotEmpty($expected);
        $this->assertEquals($expected, $actual);
    }

    public function testUnavailableCombination(): void
    {
        try {
            $this->getQuery(OrderExplicitGroupingSetsSummary::class)
                ->withDimensions('customerRegion', 'customerGender')
                ->getResult()
                ->find('@values', 'count');

            $this->fail('Expected GroupingSetNotAvailable exception.');
        } catch (GroupingSetNotAvailable $e) {
            $this->assertSame(OrderExplicitGroupingSetsSummary::class, $e->getSummaryClass());
            $this->assertEqualsCanonicalizing(
                ['customerRegion', 'customerGender'],
                $e->getDimensions(),
            );
            $this->assertStringContainsString('customerRegion', $e->getMessage());
            $this->assertStringContainsString('(customerRegion)', $e->getMessage());
            $this->assertStringContainsString(
                'Customer Region',
                $e->trans(new IdentityTranslator()),
            );
        }
    }

    public function testUnavailableCombinationWithDice(): void
    {
        $this->expectException(GroupingSetNotAvailable::class);

        $this->getQuery(OrderExplicitGroupingSetsSummary::class)
            ->withDimensions('customerRegion')
            ->dice(Criteria::expr()->isNull('customerGender'))
            ->getResult()
            ->find('@values', 'count');
    }

    //
    // helpers
    //

    /**
     * Flattens a result to a map of coordinates to measure values.
     *
     * @param list<string> $dimensions
     * @return array<string,array{count:mixed,price:mixed}>
     */
    private function collect(CubeCell $cell, array $dimensions): array
    {
        $result = [];
        $this->doCollect($cell, $dimensions, '', $result);
        ksort($result);

        return $result;
    }

    /**
     * @param list<string> $dimensions
     * @param array<string,array{count:mixed,price:mixed}> $result
     */
    private function doCollect(
        CubeCell $cell,
        array $dimensions,
        string $prefix,
        array &$result,
    ): void {
        $dimension = array_shift($dimensions);

        if ($dimension === null) {
            /** @psalm-suppress MixedAssignment */
            $count = $cell->getMeasures()->get('count')?->getRawValue();
            /** @psalm-suppress MixedAssignment */
            $price = $cell->getMeasures()->get('price')?->getRawValue();

            // OrderSummary and the explicit summary use different property
            // types for price, normalize to integer
            $result[$prefix] = [
                'count' => is_numeric($count) ? (int) $count : $count,
                'price' => is_numeric($price) ? (int) $price : $price,
            ];

            return;
        }

        foreach ($cell->drillDown($dimension) as $child) {
            /** @psalm-suppress MixedAssignment */
            $member = $child->getCoordinates()->get($dimension)?->getRawMember();

            $this->doCollect(
                $child,
                $dimensions,
                $prefix . '|' . $this->normalize($member),
                $result,
            );
        }
    }

    private function normalize(mixed $member): string
    {
        if ($member === null) {
            return 'null';
        }

        if ($member instanceof \UnitEnum) {
            return $member::class . '::' . $member->name;
        }

        if (\is_object($member) && method_exists($member, 'getId')) {
            /** @psalm-suppress MixedAssignment */
            $id = $member->getId();

            return $member::class . '#' . (\is_scalar($id) || $id instanceof \Stringable ? (string) $id : get_debug_type($id));
        }

        if ($member instanceof \Stringable || \is_scalar($member)) {
            return (string) $member;
        }

        return get_debug_type($member);
    }
}
