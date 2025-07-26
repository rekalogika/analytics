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
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\CustomerType;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DistinctValuesResolverTest extends KernelTestCase
{
    public function testDoctrineEntity(): void
    {
        $distinctValuesResolver = self::getContainer()
            ->get(DistinctValuesResolver::class);

        $values = $distinctValuesResolver->getDistinctValues(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            limit: 100,
        );

        $this->assertIsIterable($values);
        /** @psalm-suppress InvalidArgument */
        $values = iterator_to_array($values);
        $this->assertCount(6, $values);
    }

    public function testEnum(): void
    {
        $distinctValuesResolver = self::getContainer()
            ->get(DistinctValuesResolver::class);

        $values = $distinctValuesResolver->getDistinctValues(
            class: OrderSummary::class,
            dimension: 'customerType',
            limit: 100,
        );

        $this->assertIsIterable($values);
        /** @psalm-suppress InvalidArgument */
        $values = iterator_to_array($values);
        $this->assertEquals([
            'individual' => CustomerType::Individual,
            'organizational' => CustomerType::Organizational,
        ], $values);
    }

    public function testValueFromIdentifier(): void
    {
        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $distinctValuesResolver = self::getContainer()
            ->get(DistinctValuesResolver::class);

        $this->assertInstanceOf(DistinctValuesResolver::class, $distinctValuesResolver);

        $country = $entityManager
            ->getRepository(Country::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Country::class, $country);

        $id = $country->getId();

        $value = $distinctValuesResolver->getValueFromIdentifier(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            id: (string) $id,
        );

        $this->assertInstanceOf(Country::class, $value);
        $this->assertEquals($country->getName(), $value->getName());
    }

    public function testIdentifierFromValue(): void
    {
        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $distinctValuesResolver = self::getContainer()
            ->get(DistinctValuesResolver::class);

        $this->assertInstanceOf(DistinctValuesResolver::class, $distinctValuesResolver);

        $country = $entityManager
            ->getRepository(Country::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Country::class, $country);

        $id = $distinctValuesResolver->getIdentifierFromValue(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            value: $country,
        );

        $this->assertIsString($id);
        $this->assertEquals((string) $country->getId(), $id);
    }
}
