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

namespace Rekalogika\Analytics\Tests\UnitTests\SummaryMetadataFactory;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Core\Partition\DefaultIntegerPartition;
use Rekalogika\Analytics\Metadata\Implementation\DefaultAttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultDimensionGroupMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultDimensionMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Order;

final class DefaultSummaryMetadataFactoryTest extends TestCase
{
    public function testUnitSignatureIsComputedFromNormalizedUnit(): void
    {
        $manager = $this->createEntityManager();

        $factory = $this->createFactory(static fn(): EntityManagerInterface => $manager);
        $metadata = $factory->getSummaryMetadata(UnitSummary::class);

        $stringUnit = $metadata->getMeasure('stringUnit')->getUnitSignature();
        $literalUnit = $metadata->getMeasure('literalUnit')->getUnitSignature();
        $otherUnit = $metadata->getMeasure('otherUnit')->getUnitSignature();
        $noUnit = $metadata->getMeasure('noUnit')->getUnitSignature();

        $this->assertNotNull($stringUnit);
        $this->assertSame($stringUnit, $literalUnit);
        $this->assertNotSame($stringUnit, $otherUnit);
        $this->assertNull($noUnit);
    }

    public function testSourceAndSummaryMustShareEntityManager(): void
    {
        $summaryManager = $this->createEntityManager();
        $sourceManager = $this->createEntityManager();

        $factory = $this->createFactory(
            static fn(string $class): EntityManagerInterface => $class === Order::class
                ? $sourceManager
                : $summaryManager,
        );

        $this->expectException(MetadataException::class);
        $this->expectExceptionMessageMatches('/must be managed by the same entity manager/');

        $factory->getSummaryMetadata(UnitSummary::class);
    }

    public function testSourceWithoutEntityManager(): void
    {
        $manager = $this->createEntityManager();

        $factory = $this->createFactory(
            static fn(string $class): ?EntityManagerInterface => $class === Order::class
                ? null
                : $manager,
        );

        $this->expectException(MetadataException::class);
        $this->expectExceptionMessageMatches('/No manager found for the source class/');

        $factory->getSummaryMetadata(UnitSummary::class);
    }

    /**
     * @param \Closure(string):?EntityManagerInterface $managerForClass
     */
    private function createFactory(\Closure $managerForClass): DefaultSummaryMetadataFactory
    {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->willReturnCallback($managerForClass);

        $attributeCollectionFactory = new DefaultAttributeCollectionFactory();

        return new DefaultSummaryMetadataFactory(
            managerRegistry: $managerRegistry,
            attributeCollectionFactory: $attributeCollectionFactory,
            dimensionMetadataFactory: new DefaultDimensionMetadataFactory(
                attributeCollectionFactory: $attributeCollectionFactory,
                dimensionGroupMetadataFactory: new DefaultDimensionGroupMetadataFactory(
                    attributeCollectionFactory: $attributeCollectionFactory,
                ),
            ),
        );
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $classMetadata = new ClassMetadata(UnitSummary::class);

        $classMetadata->mapEmbedded([
            'fieldName' => 'partition',
            'class' => DefaultIntegerPartition::class,
            'columnPrefix' => null,
        ]);

        foreach (['stringUnit', 'literalUnit', 'otherUnit', 'noUnit'] as $field) {
            $classMetadata->mapField([
                'fieldName' => $field,
                'type' => Types::INTEGER,
            ]);
        }

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        return $manager;
    }
}
