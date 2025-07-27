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
use Rekalogika\Analytics\Contracts\MemberValuesManager;
use Rekalogika\Analytics\Serialization\Expression\DeserializationVisitor;
use Rekalogika\Analytics\Serialization\Expression\SerializationVisitor;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SerializationTest extends KernelTestCase
{
    public function testExpression(): void
    {
        $country = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Country::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Country::class, $country);

        $memberValuesManager = self::getContainer()
            ->get(MemberValuesManager::class);

        $serializationVisitor = new SerializationVisitor(
            memberValuesManager: $memberValuesManager,
            summaryClass: OrderSummary::class,
        );

        $expression = Criteria::expr()
            ->in('customerCountry', [$country]);

        $serializedExpression = $expression->visit($serializationVisitor);
        $this->assertInstanceOf(Expression::class, $serializedExpression);

        $this->assertEquals(
            expected: Criteria::expr()->in('customerCountry', [$country->getId()]),
            actual: $serializedExpression,
        );

        $deserializationVisitor = new DeserializationVisitor(
            memberValuesManager: $memberValuesManager,
            summaryClass: OrderSummary::class,
        );

        $deserializedExpression = $serializedExpression->visit($deserializationVisitor);
        $this->assertInstanceOf(Expression::class, $deserializedExpression);

        $this->assertEquals($expression, $deserializedExpression, 'Deserialized expression should match the original expression.');
    }
}
