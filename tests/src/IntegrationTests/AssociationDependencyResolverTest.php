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
use Rekalogika\Analytics\Tests\App\Entity\Category;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\Region;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AssociationDependencyResolverTest extends KernelTestCase
{
    /**
     * A null property is a dependency on the existence of the related
     * entity.
     *
     * @return iterable<string,array{class-string,string,list<array{class-string,string|null,string}>}>
     */
    public static function provideDependencies(): iterable
    {
        yield 'property of the source' => [Order::class, 'time', []];

        yield 'association of the source' => [
            Order::class,
            'customer',
            [[Customer::class, null, 'customer']],
        ];

        yield 'alias of a related entity' => [
            Order::class,
            'customer.*',
            [[Customer::class, null, 'customer']],
        ];

        yield 'property of a related entity' => [
            Order::class,
            'item.price',
            [
                [Item::class, null, 'item'],
                [Item::class, 'price', 'item'],
            ],
        ];

        yield 'association of a related entity' => [
            Order::class,
            'item.category',
            [
                [Item::class, null, 'item'],
                [Item::class, 'category', 'item'],
                [Category::class, null, 'item.category'],
            ],
        ];

        yield 'two hops' => [
            Order::class,
            'customer.country.region',
            [
                [Customer::class, null, 'customer'],
                [Customer::class, 'country', 'customer'],
                [Country::class, null, 'customer.country'],
                [Country::class, 'region', 'customer.country'],
                [Region::class, null, 'customer.country.region'],
            ],
        ];

        yield 'cast of an association' => [
            Order::class,
            'customer(' . IndividualCustomer::class . ').gender',
            [
                [IndividualCustomer::class, null, 'customer'],
                [IndividualCustomer::class, 'gender', 'customer'],
            ],
        ];

        yield 'cast of an association, then another hop' => [
            Order::class,
            'customer(' . IndividualCustomer::class . ').country.region',
            [
                [IndividualCustomer::class, null, 'customer'],
                [IndividualCustomer::class, 'country', 'customer'],
                [Country::class, null, 'customer(' . IndividualCustomer::class . ').country'],
                [Country::class, 'region', 'customer(' . IndividualCustomer::class . ').country'],
                [Region::class, null, 'customer(' . IndividualCustomer::class . ').country.region'],
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
            [
                [Country::class, null, '(' . IndividualCustomer::class . ').country'],
                [Country::class, 'region', '(' . IndividualCustomer::class . ').country'],
                [Region::class, null, '(' . IndividualCustomer::class . ').country.region'],
            ],
        ];

        yield 'to-many association' => [
            Order::class,
            'customer.orders.time',
            [[Customer::class, null, 'customer']],
        ];

        yield 'unknown property' => [
            Order::class,
            'customer.foo.bar',
            [[Customer::class, null, 'customer']],
        ];
    }

    /**
     * @param class-string $sourceClass
     * @param list<array{class-string,string|null,string}> $expected
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
     * @return iterable<string,array{class-string,string,string|null}>
     */
    public static function provideToManyAssociations(): iterable
    {
        yield 'property of the source' => [Order::class, 'time', null];
        yield 'to-one associations' => [Order::class, 'customer.country.region', null];
        yield 'alias of a related entity' => [Order::class, 'customer.*', null];
        yield 'to-many association of the source' => [Customer::class, 'orders.time', 'orders'];
        yield 'to-many association as the leaf' => [Customer::class, 'orders', 'orders'];
        yield 'to-many association of a related entity' => [Order::class, 'customer.orders.time', 'customer.orders'];

        yield 'cast of an association' => [
            Order::class,
            'customer(' . IndividualCustomer::class . ').orders.time',
            'customer(' . IndividualCustomer::class . ').orders',
        ];

        yield 'root cast' => [
            Customer::class,
            '(' . IndividualCustomer::class . ').orders.time',
            '(' . IndividualCustomer::class . ').orders',
        ];

        yield 'unknown property' => [Order::class, 'customer.foo.orders', null];
    }

    /**
     * @param class-string $sourceClass
     */
    #[DataProvider('provideToManyAssociations')]
    public function testFindToManyAssociation(string $sourceClass, string $path, ?string $expected): void
    {
        $resolver = new AssociationDependencyResolver(
            static::getContainer()->get(ManagerRegistry::class),
        );

        $this->assertSame($expected, $resolver->findToManyAssociation($sourceClass, $path));
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
