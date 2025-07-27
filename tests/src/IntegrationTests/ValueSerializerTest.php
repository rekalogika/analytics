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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ValueSerializerTest extends KernelTestCase
{
    public function testValueFromIdentifier(): void
    {
        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $valueSerializer = self::getContainer()
            ->get(ValueSerializer::class);

        $this->assertInstanceOf(ValueSerializer::class, $valueSerializer);

        $country = $entityManager
            ->getRepository(Country::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Country::class, $country);

        $id = $country->getId();

        $value = $valueSerializer->deserialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            identifier: (string) $id,
        );

        $this->assertInstanceOf(Country::class, $value);
        $this->assertEquals($country->getName(), $value->getName());
    }

    public function testIdentifierFromValue(): void
    {
        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $valueSerializer = self::getContainer()
            ->get(ValueSerializer::class);

        $this->assertInstanceOf(ValueSerializer::class, $valueSerializer);

        $country = $entityManager
            ->getRepository(Country::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Country::class, $country);

        $id = $valueSerializer->serialize(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            value: $country,
        );

        $this->assertIsString($id);
        $this->assertEquals((string) $country->getId(), $id);
    }
}
