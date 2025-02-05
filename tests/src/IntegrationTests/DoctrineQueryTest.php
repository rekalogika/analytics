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
use Doctrine\ORM\Query\Parser;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineQueryTest extends KernelTestCase
{
    /**
     * @todo WIP
     */
    public function testQueryBuilderToSql(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([], ['id' => 'ASC']);

        $this->assertInstanceOf(Customer::class, $customer);

        /** @psalm-suppress QueryBuilderSetParameter */
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.id = :id')
            ->andWhere('o.id = :id')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.time = :time')
            ->setParameter('id', 1)
            ->setParameter('customer', $customer)
            ->setParameter('time', new \DateTimeImmutable('2021-01-01 00:00:00'));

        $query = $queryBuilder->getQuery();
        $sql = $query->getSQL();
        $parser = new Parser($query);
        $parserResult = $parser->parse();
        $parametersMapping = $parserResult->getParameterMappings();

        $this->assertEquals('SELECT o0_.id AS id_0, o0_.time AS time_1, o0_.item_id AS item_id_2, o0_.customer_id AS customer_id_3 FROM "order" o0_ WHERE o0_.id = ? AND o0_.id = ? AND o0_.customer_id = ? AND o0_.customer_id = ? AND o0_.time = ?', $sql);

        $this->assertEquals([
            'id' => [0,1],
            'customer' => [2,3],
            'time' => [4],
        ], $parametersMapping);
    }
}
