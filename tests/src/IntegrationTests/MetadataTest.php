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

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Metadata\Attribute\Partition;
use Rekalogika\Analytics\Metadata\Attribute\PartitionKey;
use Rekalogika\Analytics\Metadata\Attribute\PartitionLevel;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Source\AssociationDependency;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\HierarchyAwareLabel;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Category;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\OccupancyHistorySummary;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderExplicitGroupingSetsSummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MetadataTest extends KernelTestCase
{
    public function testSourceMetadataForOrder(): void
    {
        $sourceMetadataFactory = static::getContainer()
            ->get(SourceMetadataFactory::class);

        $orderMetadata = $sourceMetadataFactory->getSourceMetadata(Order::class);

        $this->assertEquals(Order::class, $orderMetadata->getClass());

        $this->assertEqualsCanonicalizing(
            [OrderSummary::class, OrderExplicitGroupingSetsSummary::class],
            $orderMetadata->getInvolvedSummaryClassesByChangedProperties(['customer']),
        );

        $this->assertEqualsCanonicalizing(
            [OrderSummary::class, OrderExplicitGroupingSetsSummary::class],
            $orderMetadata->getInvolvedSummaryClassesByChangedProperties(['customer', 'item']),
        );

        $this->assertEquals(
            [],
            $orderMetadata->getInvolvedSummaryClassesByChangedProperties(['foo']),
        );
    }

    public function testSourceMetadataForCustomer(): void
    {
        $sourceMetadataFactory = static::getContainer()
            ->get(SourceMetadataFactory::class);

        $customerMetadata = $sourceMetadataFactory->getSourceMetadata(Customer::class);

        $this->assertEquals(Customer::class, $customerMetadata->getClass());

        $this->assertEquals(
            [CustomerSummary::class],
            $customerMetadata->getInvolvedSummaryClassesByChangedProperties(['country']),
        );

        $this->assertEquals(
            [],
            $customerMetadata->getInvolvedSummaryClassesByChangedProperties(['foo']),
        );
    }

    public function testSourceMetadataForIndividualCustomer(): void
    {
        $sourceMetadataFactory = static::getContainer()
            ->get(SourceMetadataFactory::class);

        $metadata = $sourceMetadataFactory->getSourceMetadata(IndividualCustomer::class);

        $this->assertEquals(IndividualCustomer::class, $metadata->getClass());

        $this->assertEquals(
            [CustomerSummary::class],
            $metadata->getInvolvedSummaryClassesByChangedProperties(['country']),
        );

        // read through a cast of the root entity: (IndividualCustomer).gender

        $this->assertEquals(
            [CustomerSummary::class],
            $metadata->getInvolvedSummaryClassesByChangedProperties(['gender']),
        );

        $this->assertArrayNotHasKey('', $metadata->getPropertyToSummaryClasses());
        $this->assertArrayNotHasKey('*', $metadata->getPropertyToSummaryClasses());
    }

    public function testAssociationDependencies(): void
    {
        $sourceMetadataFactory = static::getContainer()
            ->get(SourceMetadataFactory::class);

        // Item is not a source, its properties are read through Order::item

        $itemMetadata = $sourceMetadataFactory->getSourceMetadata(Item::class);

        $this->assertSame([], $itemMetadata->getAllInvolvedSummaryClasses());
        $this->assertSame([], $itemMetadata->getInvolvedSummaryClassesByChangedProperties(['category']));

        $this->assertEqualsCanonicalizing(
            [
                [Order::class, 'item', OrderSummary::class],
                [Order::class, 'item', OrderExplicitGroupingSetsSummary::class],
            ],
            $this->formatDependencies(
                $itemMetadata->getAssociationDependenciesByChangedProperties(['category']),
            ),
        );

        $this->assertEqualsCanonicalizing(
            [[Order::class, 'item', OrderSummary::class]],
            $this->formatDependencies(
                $itemMetadata->getAssociationDependenciesByChangedProperties(['countryOfOrigin']),
            ),
        );

        $this->assertSame([], $itemMetadata->getAssociationDependenciesByChangedProperties(['name']));

        // Country::region is read by the summaries of Order and Customer,
        // through different paths

        $countryMetadata = $sourceMetadataFactory->getSourceMetadata(Country::class);

        $this->assertEqualsCanonicalizing(
            [
                [Order::class, 'customer.country', OrderSummary::class],
                [Order::class, 'customer.country', OrderExplicitGroupingSetsSummary::class],
                [Customer::class, 'country', CustomerSummary::class],
            ],
            $this->formatDependencies(
                $countryMetadata->getAssociationDependenciesByChangedProperties(['region']),
            ),
        );

        // the source entities refer to the country through the same paths,
        // and through item.countryOfOrigin. its deletion affects them

        $this->assertEqualsCanonicalizing(
            [
                [Order::class, 'customer.country', OrderSummary::class],
                [Order::class, 'customer.country', OrderExplicitGroupingSetsSummary::class],
                [Order::class, 'item.countryOfOrigin', OrderSummary::class],
                [Customer::class, 'country', CustomerSummary::class],
            ],
            $this->formatDependencies($countryMetadata->getExistenceDependencies()),
        );

        $this->assertCount(7, $countryMetadata->getAllAssociationDependencies());

        // Category is not read, but Item::category refers to it

        $categoryMetadata = $sourceMetadataFactory->getSourceMetadata(Category::class);

        $this->assertSame([], $categoryMetadata->getPropertyToAssociationDependencies());

        $this->assertEqualsCanonicalizing(
            [
                [Order::class, 'item.category', OrderSummary::class],
                [Order::class, 'item.category', OrderExplicitGroupingSetsSummary::class],
            ],
            $this->formatDependencies($categoryMetadata->getAllAssociationDependencies()),
        );

        // Customer is a source itself, and is also read through Order::customer

        $customerMetadata = $sourceMetadataFactory->getSourceMetadata(IndividualCustomer::class);

        $this->assertEquals(
            [CustomerSummary::class],
            $customerMetadata->getInvolvedSummaryClassesByChangedProperties(['country']),
        );

        $this->assertEqualsCanonicalizing(
            [
                [Order::class, 'customer', OrderSummary::class],
                [Order::class, 'customer', OrderExplicitGroupingSetsSummary::class],
            ],
            $this->formatDependencies(
                $customerMetadata->getAssociationDependenciesByChangedProperties(['country']),
            ),
        );

        // read through a cast to IndividualCustomer, so only for that class

        $this->assertCount(
            2,
            $customerMetadata->getAssociationDependenciesByChangedProperties(['gender']),
        );

        $this->assertSame(
            [],
            $sourceMetadataFactory
                ->getSourceMetadata(Customer::class)
                ->getAssociationDependenciesByChangedProperties(['gender']),
        );

        // nothing reads Order through an association

        $this->assertSame(
            [],
            $sourceMetadataFactory->getSourceMetadata(Order::class)->getAllAssociationDependencies(),
        );
    }

    public function testAssociationTrackingDisabled(): void
    {
        $summaryMetadataFactory = static::getContainer()
            ->get(SummaryMetadataFactory::class);

        $metadata = $summaryMetadataFactory->getSummaryMetadata(OrderSummary::class);

        $this->assertTrue($metadata->isTrackAssociations());

        $untrackedMetadata = new SummaryMetadata(
            sourceClass: $metadata->getSourceClass(),
            summaryClass: $metadata->getSummaryClass(),
            partition: $metadata->getPartition(),
            dimensions: $metadata->getRootDimensions(),
            measures: $metadata->getMeasures(),
            groupingsProperty: $metadata->getGroupingsProperty(),
            attributes: $metadata->getAttributes(),
            label: $metadata->getLabel(),
            trackAssociations: false,
        );

        $sourceMetadataFactory = new DefaultSourceMetadataFactory(
            summaryMetadataFactory: new class ($untrackedMetadata) implements SummaryMetadataFactory {
                public function __construct(private readonly SummaryMetadata $metadata) {}

                #[\Override]
                public function getSummaryMetadata(string $summaryClassName): SummaryMetadata
                {
                    return $this->metadata;
                }

                #[\Override]
                public function getSummaryClasses(): array
                {
                    return [$this->metadata->getSummaryClass()];
                }

                #[\Override]
                public function isSummary(string $className): bool
                {
                    return $className === $this->metadata->getSummaryClass();
                }
            },
            managerRegistry: static::getContainer()->get(ManagerRegistry::class),
        );

        $this->assertSame(
            [],
            $sourceMetadataFactory->getSourceMetadata(Item::class)->getAllAssociationDependencies(),
        );

        // the source itself is still tracked

        $this->assertEquals(
            [OrderSummary::class],
            $sourceMetadataFactory
                ->getSourceMetadata(Order::class)
                ->getInvolvedSummaryClassesByChangedProperties(['item']),
        );
    }

    /**
     * @param list<AssociationDependency> $dependencies
     * @return list<array{class-string,string,class-string}>
     */
    private function formatDependencies(array $dependencies): array
    {
        return array_map(
            static fn(AssociationDependency $dependency): array => [
                $dependency->getSourceClass(),
                $dependency->getReversePath(),
                $dependency->getSummaryClass(),
            ],
            $dependencies,
        );
    }

    public function testPartitionMetadataAttributes(): void
    {
        $attributes = static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata(OrderSummary::class)
            ->getPartition()
            ->getAttributes();

        $this->assertTrue($attributes->hasAttribute(Partition::class));
        $this->assertFalse($attributes->hasAttribute(PartitionKey::class));
        $this->assertFalse($attributes->hasAttribute(PartitionLevel::class));
    }

    /**
     * @return iterable<string,array{string}>
     */
    public static function provideDimensionName(): iterable
    {
        yield 'root dimension' => ['customerCountry'];
        yield 'child dimension' => ['time.civil.month.month'];
    }

    #[DataProvider('provideDimensionName')]
    public function testRawLabelIsNotHierarchyAware(string $name): void
    {
        $dimension = static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata(OrderSummary::class)
            ->getDimension($name);

        $this->assertNotInstanceOf(HierarchyAwareLabel::class, $dimension->getRawLabel());
        $this->assertSame($dimension->getRawLabel(), $dimension->getLabel()->getLeaf());
    }

    /**
     * A source property used only by the partition must still be an involved
     * property, a change to it moves the entity to another partition.
     */
    public function testInvolvedSourcePropertiesIncludePartition(): void
    {
        $summaryMetadataFactory = static::getContainer()
            ->get(SummaryMetadataFactory::class);

        $metadata = $summaryMetadataFactory->getSummaryMetadata(OrderSummary::class);

        // the partition of OccupancyHistorySummary uses the `date` property,
        // OrderSummary does not use it

        $partition = $summaryMetadataFactory
            ->getSummaryMetadata(OccupancyHistorySummary::class)
            ->getPartition();

        $this->assertSame(['date'], $partition->getSource()->getInvolvedProperties());
        $this->assertNotContains('date', $metadata->getInvolvedSourceProperties());

        $newMetadata = new SummaryMetadata(
            sourceClass: $metadata->getSourceClass(),
            summaryClass: $metadata->getSummaryClass(),
            partition: $partition,
            dimensions: $metadata->getRootDimensions(),
            measures: $metadata->getMeasures(),
            groupingsProperty: $metadata->getGroupingsProperty(),
            attributes: $metadata->getAttributes(),
            label: $metadata->getLabel(),
        );

        $this->assertContains('date', $newMetadata->getInvolvedSourceProperties());
    }
}
