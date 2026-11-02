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

namespace Rekalogika\Analytics\Tests\IntegrationTests\ValueSerializer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Bundle\Serialization\ChainValueSerializer;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Serialization\UnsupportedValue;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Engine\Serialization\DoctrineValueSerializer;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\EmptyEntitySummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\TestIntegerPartition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ValueSerializerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ValueSerializer $valueSerializer;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        $this->valueSerializer = self::getContainer()
            ->get(ValueSerializer::class);
    }

    public function testEntityDeserialization(): void
    {
        $country = $this->entityManager
            ->getRepository(Country::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Country::class, $country);

        $id = $country->getId();

        $value = $this->valueSerializer->deserialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            identifier: (string) $id,
        );

        $this->assertInstanceOf(Country::class, $value);
        $this->assertEquals($country->getName(), $value->getName());
    }

    public function testEntitySerialization(): void
    {
        $country = $this->entityManager
            ->getRepository(Country::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Country::class, $country);

        $id = $this->valueSerializer->serialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            value: $country,
        );

        $this->assertEquals((string) $country->getId(), $id);
    }

    public function testIntegerSerialization(): void
    {
        $value = 202301;

        $id = $this->valueSerializer->serialize(
            class: OrderSummary::class,
            dimension: 'time.civil.month.month',
            value: $value,
        );

        $this->assertEquals($value, $id);
    }

    public function testIntegerDeserialization(): void
    {
        $value = 202301;

        /** @psalm-suppress MixedAssignment */
        $id = $this->valueSerializer->deserialize(
            class: OrderSummary::class,
            dimension: 'time.civil.month.month',
            identifier: (string) $value,
        );

        $this->assertEquals($value, $id);
    }

    public function testNullSerialization(): void
    {
        $id = $this->valueSerializer->serialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            value: null,
        );

        $this->assertNull($id);
    }

    public function testNullDeserialization(): void
    {
        $id = $this->valueSerializer->deserialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            identifier: null,
        );

        $this->assertNull($id);
    }

    public function testBooleanSerialization(): void
    {
        $this->assertSame('true', $this->valueSerializer->serialize(
            class: EmptyEntitySummary::class,
            dimension: 'active',
            value: true,
        ));

        $this->assertSame('false', $this->valueSerializer->serialize(
            class: EmptyEntitySummary::class,
            dimension: 'active',
            value: false,
        ));
    }

    public function testBooleanDeserialization(): void
    {
        $this->assertTrue($this->valueSerializer->deserialize(
            class: EmptyEntitySummary::class,
            dimension: 'active',
            identifier: 'true',
        ));

        $this->assertFalse($this->valueSerializer->deserialize(
            class: EmptyEntitySummary::class,
            dimension: 'active',
            identifier: 'false',
        ));
    }

    public function testInvalidBooleanDeserialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->valueSerializer->deserialize(
            class: EmptyEntitySummary::class,
            dimension: 'active',
            identifier: 'foo',
        );
    }

    public function testNonEntityObjectIsUnsupported(): void
    {
        $this->expectException(UnsupportedValue::class);

        $this->createDoctrineValueSerializer()->serialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            value: new \DateTimeImmutable(),
        );
    }

    public function testEmbeddableIsUnsupported(): void
    {
        $this->expectException(UnsupportedValue::class);

        $this->createDoctrineValueSerializer()->serialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            value: TestIntegerPartition::createFromSourceValue(1, 16),
        );
    }

    public function testChainFallsThroughToNextSerializer(): void
    {
        $dateSerializer = new class implements ValueSerializer {
            #[\Override]
            public function serialize(
                string $class,
                string $dimension,
                mixed $value,
            ): string {
                if (!$value instanceof \DateTimeInterface) {
                    throw new UnsupportedValue();
                }

                return $value->format('Y-m-d');
            }

            #[\Override]
            public function deserialize(
                string $class,
                string $dimension,
                int|string|null $identifier,
            ): mixed {
                return new \DateTimeImmutable((string) $identifier);
            }
        };

        $chain = new ChainValueSerializer(
            specificServiceLocator: new ServiceLocator([]),
            nonSpecificServices: [
                $this->createDoctrineValueSerializer(),
                $dateSerializer,
            ],
        );

        $this->assertSame('2025-01-31', $chain->serialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            value: new \DateTimeImmutable('2025-01-31'),
        ));
    }

    private function createDoctrineValueSerializer(): DoctrineValueSerializer
    {
        return new DoctrineValueSerializer(
            managerRegistry: self::getContainer()->get(ManagerRegistry::class),
            summaryMetadataFactory: self::getContainer()
                ->get(SummaryMetadataFactory::class),
        );
    }
}
