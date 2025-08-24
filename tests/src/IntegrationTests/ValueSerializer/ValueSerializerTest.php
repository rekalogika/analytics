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
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
}
