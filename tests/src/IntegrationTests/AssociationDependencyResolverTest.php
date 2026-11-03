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

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Metadata\Implementation\AssociationDependencyResolver;
use Rekalogika\Analytics\Metadata\Source\AssociationDependency;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AssociationDependencyResolverTest extends KernelTestCase
{
    /**
     * @return iterable<string,array{class-string,string,list<array{class-string,string,string}>}>
     */
    public static function provideDependencies(): iterable
    {
        yield 'property of the source' => [Order::class, 'time', []];
        yield 'association of the source' => [Order::class, 'customer', []];
        yield 'alias of a related entity' => [Order::class, 'customer.*', []];

        yield 'property of a related entity' => [
            Order::class,
            'item.price',
            [[Item::class, 'price', 'item']],
        ];

        yield 'association of a related entity' => [
            Order::class,
            'item.category',
            [[Item::class, 'category', 'item']],
        ];

        yield 'two hops' => [
            Order::class,
            'customer.country.region',
            [
                [Customer::class, 'country', 'customer'],
                [Country::class, 'region', 'customer.country'],
            ],
        ];

        yield 'cast of an association' => [
            Order::class,
            'customer(' . IndividualCustomer::class . ').gender',
            [[IndividualCustomer::class, 'gender', 'customer']],
        ];

        yield 'cast of an association, then another hop' => [
            Order::class,
            'customer(' . IndividualCustomer::class . ').country.region',
            [
                [IndividualCustomer::class, 'country', 'customer'],
                [Country::class, 'region', 'customer(' . IndividualCustomer::class . ').country'],
            ],
        ];

        yield 'root cast' => [
            Customer::class,
            '(' . IndividualCustomer::class . ').gender',
            [],
        ];

        yield 'root cast, then a hop' => [
            Customer::class,
            '(' . IndividualCustomer::class . ').country.region',
            [[Country::class, 'region', '(' . IndividualCustomer::class . ').country']],
        ];

        yield 'to-many association' => [Order::class, 'customer.orders.time', []];
        yield 'unknown property' => [Order::class, 'customer.foo.bar', []];
    }

    /**
     * @param class-string $sourceClass
     * @param list<array{class-string,string,string}> $expected
     */
    #[DataProvider('provideDependencies')]
    public function testResolve(string $sourceClass, string $path, array $expected): void
    {
        $resolver = new AssociationDependencyResolver(
            static::getContainer()->get(ManagerRegistry::class),
        );

        $dependencies = $resolver->resolve(
            sourceClass: $sourceClass,
            path: $path,
            summaryClass: OrderSummary::class,
        );

        $this->assertSame(
            $expected,
            array_map(
                static fn(AssociationDependency $dependency): array => [
                    $dependency->getRelatedClass(),
                    $dependency->getProperty(),
                    $dependency->getReversePath(),
                ],
                $dependencies,
            ),
        );

        foreach ($dependencies as $dependency) {
            $this->assertSame($sourceClass, $dependency->getSourceClass());
            $this->assertSame(OrderSummary::class, $dependency->getSummaryClass());
            $this->assertReversePathIsQueryable($dependency);
        }
    }

    /**
     * The reverse path is used to query the source entities referring to a
     * related entity.
     */
    private function assertReversePathIsQueryable(AssociationDependency $dependency): void
    {
        $queryBuilder = SimpleQueryBuilder::createFromRegistry(
            managerRegistry: static::getContainer()->get(ManagerRegistry::class),
            from: $dependency->getSourceClass(),
        );

        $expression = $queryBuilder->resolve($dependency->getReversePath());

        $sql = $queryBuilder
            ->select('COUNT(root)')
            ->andWhere(\sprintf('%s IN (:entities)', $expression))
            ->setParameter('entities', [1])
            ->getQuery()
            ->getSQL();

        $this->assertIsString($sql);
    }
}
