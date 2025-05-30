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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\ORM\Query\Parser;
use Rekalogika\Analytics\Doctrine\QueryExtractor;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineQueryTest extends KernelTestCase
{
    /**
     * @see Query::_doExecute
     */
    public function testQueryBuilderToSql(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([], ['id' => 'ASC']);

        $items = $entityManager
            ->getRepository(Item::class)
            ->findBy([], [], 3);

        $itemIds = array_map(
            static fn(Item $item): ?int => $item->getId(),
            $items,
        );

        $this->assertInstanceOf(Customer::class, $customer);
        $uuid = $customer->getId()->toRfc4122();

        /** @psalm-suppress QueryBuilderSetParameter */
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.id = :id')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.id = :id')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.time = :time')
            ->andWhere('o.item IN (:items)')
            ->setParameter('id', 1)
            ->setParameter('customer', $customer)
            ->setParameter('time', new \DateTimeImmutable('2021-01-01 00:00:00'))
            ->setParameter('items', $items);

        $query = $queryBuilder->getQuery();

        $parser = new Parser($query);
        $parserResult = $parser->parse();
        $sqlExecutor = $parserResult->prepareSqlExecutor($query);
        $sqlStatements = $sqlExecutor->getSqlStatements();
        $resultSetMapping = $parserResult->getResultSetMapping();

        $this->assertTrue($resultSetMapping->isSelect);

        if (\is_string($sqlStatements)) {
            $sql = $sqlStatements;
        } else {
            $sql = $sqlStatements[0];
        }

        $this->assertEquals('SELECT o0_.id AS id_0, o0_.time AS time_1, o0_.item_id AS item_id_2, o0_.customer_id AS customer_id_3 FROM "order" o0_ WHERE o0_.id = ? AND o0_.customer_id = ? AND o0_.id = ? AND o0_.customer_id = ? AND o0_.time = ? AND o0_.item_id IN (?)', $sql);

        $parametersMapping = $parserResult->getParameterMappings();

        $this->assertEquals([
            'id' => [0, 2],
            'customer' => [1, 3],
            'time' => [4],
            'items' => [5],
        ], $parametersMapping);

        $bindValues = [];

        foreach ($parametersMapping as $key => $positions) {
            $parameter = $query->getParameter($key);

            if ($parameter === null) {
                throw new \LogicException('Parameter not found');
            }

            /** @psalm-suppress MixedAssignment */
            $originalValue = $parameter->getValue();
            /** @psalm-suppress MixedAssignment */
            $processedValue = $query->processParameterValue($originalValue);

            if ($originalValue === $processedValue) {
                $type = $parameter->getType();
            } else {
                $type = ParameterTypeInferer::inferType($processedValue);
            }

            if (
                !\is_string($type)
                && !\is_int($type)
                && !$type instanceof ArrayParameterType
                && !$type instanceof ParameterType
            ) {
                throw new \LogicException('Invalid type');
            }

            foreach ($positions as $position) {

                $bindValues[$position] = [$processedValue, $type];
            }
        }

        ksort($bindValues);

        $this->assertEquals([
            0 => [1, 'integer'],
            1 => [$uuid, ParameterType::STRING],
            2 => [1, 'integer'],
            3 => [$uuid, ParameterType::STRING],
            4 => [new \DateTimeImmutable('2021-01-01 00:00:00'), 'datetime_immutable'],
            5 => [$itemIds, ArrayParameterType::INTEGER],
        ], $bindValues);
    }

    public function testQueryExtractor(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([], ['id' => 'ASC']);

        $items = $entityManager
            ->getRepository(Item::class)
            ->findBy([], [], 3);

        $itemIds = array_map(
            static fn(Item $item): ?int => $item->getId(),
            $items,
        );

        $this->assertInstanceOf(Customer::class, $customer);
        $uuid = $customer->getId()->toRfc4122();

        /** @psalm-suppress QueryBuilderSetParameter */
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.id = :id')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.id = :id')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.time = :time')
            ->andWhere('o.item IN (:items)')
            ->setParameter('id', 1)
            ->setParameter('customer', $customer)
            ->setParameter('time', new \DateTimeImmutable('2021-01-01 00:00:00'))
            ->setParameter('items', $items);

        $query = $queryBuilder->getQuery();

        $queryExtractor = new QueryExtractor($query);

        $this->assertTrue($queryExtractor->getResultSetMapping()->isSelect);
        $sqlStatement = $queryExtractor->getSqlStatement();

        $this->assertEquals('SELECT o0_.id AS id_0, o0_.time AS time_1, o0_.item_id AS item_id_2, o0_.customer_id AS customer_id_3 FROM "order" o0_ WHERE o0_.id = ? AND o0_.customer_id = ? AND o0_.id = ? AND o0_.customer_id = ? AND o0_.time = ? AND o0_.item_id IN (?)', $sqlStatement);

        $parameters = $queryExtractor->getParameters();

        $this->assertEquals([
            0 => [1, 'integer'],
            1 => [$uuid, ParameterType::STRING],
            2 => [1, 'integer'],
            3 => [$uuid, ParameterType::STRING],
            4 => [new \DateTimeImmutable('2021-01-01 00:00:00'), 'datetime_immutable'],
            5 => [$itemIds, ArrayParameterType::INTEGER],
        ], $parameters);
    }
}
