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
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class NextValFunctionTest extends KernelTestCase
{
    public function testSql(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $sequence = $entityManager
            ->getClassMetadata(OrderSummary::class)
            ->getSequenceName($entityManager->getConnection()->getDatabasePlatform());

        $sql = $entityManager
            ->createQuery(\sprintf(
                'SELECT REKALOGIKA_NEXTVAL(%s) FROM %s o',
                OrderSummary::class,
                Order::class,
            ))
            ->getSQL();

        $this->assertIsString($sql);
        $this->assertStringContainsString(
            \sprintf("SELECT NEXTVAL('%s') AS", $sequence),
            $sql,
        );
    }

    public function testEveryRowGetsADistinctValue(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        /** @var list<array{id:int}> */
        $result = $entityManager
            ->createQuery(\sprintf(
                'SELECT REKALOGIKA_NEXTVAL(%s) AS id FROM %s o',
                OrderSummary::class,
                Order::class,
            ))
            ->setMaxResults(5)
            ->getScalarResult();

        $ids = array_column($result, 'id');

        $this->assertCount(5, $ids);
        $this->assertCount(5, array_unique($ids));
    }
}
