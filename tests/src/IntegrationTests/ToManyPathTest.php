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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Bundle\Command\DebugSourceCommand;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\Filter\SourceCondition;
use Rekalogika\Analytics\Metadata\Attribute\Measure;
use Rekalogika\Analytics\Metadata\Attribute\Summary;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Source\ToManyPath;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\IntegrationTests\Fixtures\OverridingAttributeCollectionFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Paths read through a to-many association: rejected in a filter, and
 * reported as untracked elsewhere.
 */
final class ToManyPathTest extends KernelTestCase
{
    /**
     * @return iterable<string,array{Expression}>
     */
    public static function provideToManyFilters(): iterable
    {
        yield 'comparison' => [new Comparison('orders.time', '>', '2024-01-01')];
        yield 'source condition' => [new SourceCondition('[orders.time] IS NOT NULL')];
    }

    #[DataProvider('provideToManyFilters')]
    public function testFilterThroughToManyAssociationIsRejected(Expression $filter): void
    {
        $factory = $this->createSummaryMetadataFactory(
            classAttributes: [
                CustomerSummary::class => [
                    new Summary(sourceClass: Customer::class, filter: $filter),
                ],
            ],
        );

        try {
            $factory->getSummaryMetadata(CustomerSummary::class);
            $this->fail('The filter should be rejected.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(MetadataException::class, $e);
            $this->assertStringContainsString('to-many association "orders"', $e->getMessage());
        }
    }

    public function testFilterThroughToOneAssociationIsAccepted(): void
    {
        $factory = $this->createSummaryMetadataFactory(
            classAttributes: [
                CustomerSummary::class => [
                    new Summary(
                        sourceClass: Customer::class,
                        filter: new Comparison('country.name', '=', 'Indonesia'),
                    ),
                ],
            ],
        );

        $this->assertNotNull(
            $factory->getSummaryMetadata(CustomerSummary::class)->getFilter(),
        );
    }

    public function testToManyPathIsReported(): void
    {
        $sourceMetadataFactory = $this->createSourceMetadataFactoryWithToManyMeasure();

        $expected = [[Customer::class, 'orders.id', 'orders', CustomerSummary::class]];

        // also seen by a subclass of the source class

        foreach ([Customer::class, IndividualCustomer::class] as $class) {
            $this->assertSame(
                $expected,
                array_map(
                    static fn(ToManyPath $toManyPath): array => [
                        $toManyPath->getSourceClass(),
                        $toManyPath->getPath(),
                        $toManyPath->getAssociation(),
                        $toManyPath->getSummaryClass(),
                    ],
                    $sourceMetadataFactory->getSourceMetadata($class)->getToManyPaths(),
                ),
            );
        }
    }

    public function testNoToManyPathInTheTestApplication(): void
    {
        $sourceMetadataFactory = static::getContainer()->get(SourceMetadataFactory::class);

        foreach ([Customer::class, Order::class] as $class) {
            $this->assertSame([], $sourceMetadataFactory->getSourceMetadata($class)->getToManyPaths());
        }
    }

    public function testDebugSourceCommand(): void
    {
        $command = new DebugSourceCommand(
            summaryMetadataFactory: $this->createSummaryMetadataFactory(
                propertyAttributes: $this->getToManyMeasure(),
            ),
            sourceMetadataFactory: $this->createSourceMetadataFactoryWithToManyMeasure(),
            managerRegistry: $this->getManagerRegistry(),
        );

        $tester = new CommandTester($command);
        $tester->execute(['sourceClass' => Customer::class]);

        $display = $this->normalizeWhitespace($tester->getDisplay());

        $this->assertStringContainsString('Untracked To-Many Paths', $display);
        $this->assertStringContainsString('A change read through the to-many association is not tracked.', $display);
        $this->assertMatchesRegularExpression('/orders\.id\s+orders\s+' . preg_quote(CustomerSummary::class, '/') . '/', $display);
    }

    public function testDebugSourceCommandWithoutToManyPaths(): void
    {
        $command = new DebugSourceCommand(
            summaryMetadataFactory: $this->createSummaryMetadataFactory(),
            sourceMetadataFactory: static::getContainer()->get(SourceMetadataFactory::class),
            managerRegistry: $this->getManagerRegistry(),
        );

        $tester = new CommandTester($command);
        $tester->execute(['sourceClass' => Customer::class]);

        $this->assertStringNotContainsString('To-Many', $tester->getDisplay());
    }

    /**
     * The count measure of the customer summary, counting the orders.
     *
     * @return array<class-string,array<string,list<object>>>
     */
    private function getToManyMeasure(): array
    {
        return [
            CustomerSummary::class => [
                'count' => [new Measure(function: new Count('orders.id'))],
            ],
        ];
    }

    private function createSourceMetadataFactoryWithToManyMeasure(): SourceMetadataFactory
    {
        return new DefaultSourceMetadataFactory(
            summaryMetadataFactory: $this->createSummaryMetadataFactory(
                propertyAttributes: $this->getToManyMeasure(),
            ),
            managerRegistry: $this->getManagerRegistry(),
        );
    }

    /**
     * @param array<class-string,list<object>> $classAttributes
     * @param array<class-string,array<string,list<object>>> $propertyAttributes
     */
    private function createSummaryMetadataFactory(
        array $classAttributes = [],
        array $propertyAttributes = [],
    ): DefaultSummaryMetadataFactory {
        $attributeCollectionFactory = static::getContainer()
            ->get('rekalogika.analytics.metadata.attribute_collection_factory');

        $this->assertInstanceOf(AttributeCollectionFactory::class, $attributeCollectionFactory);

        $dimensionMetadataFactory = static::getContainer()
            ->get('rekalogika.analytics.dimension_metadata_factory');

        $this->assertInstanceOf(DimensionMetadataFactory::class, $dimensionMetadataFactory);

        return new DefaultSummaryMetadataFactory(
            managerRegistry: $this->getManagerRegistry(),
            attributeCollectionFactory: new OverridingAttributeCollectionFactory(
                decorated: $attributeCollectionFactory,
                classAttributes: $classAttributes,
                propertyAttributes: $propertyAttributes,
            ),
            dimensionMetadataFactory: $dimensionMetadataFactory,
        );
    }

    private function getManagerRegistry(): ManagerRegistry
    {
        return static::getContainer()->get(ManagerRegistry::class);
    }

    private function normalizeWhitespace(string $string): string
    {
        return preg_replace('/\s+/', ' ', $string) ?? $string;
    }
}
