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

namespace Rekalogika\Analytics\Tests\SimpleQueryBuilder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\SimpleQueryBuilder\Path\PathResolver;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PathResolverTest extends KernelTestCase
{
    /**
     * @param class-string $class
     */
    private function createPathResolver(
        string $class,
        string $alias,
    ): PathResolver {
        $manager = self::getContainer()
            ->get(ManagerRegistry::class)
            ->getManagerForClass($class);

        $this->assertInstanceOf(EntityManagerInterface::class, $manager);

        $queryBuilder = $manager
            ->createQueryBuilder()
            ->from($class, $alias)
            ->select($alias . '.id');

        return new PathResolver(
            baseClass: $class,
            baseAlias: $alias,
            queryBuilder: $queryBuilder,
        );
    }

    //
    // ROOT
    //

    public function testRootProperty(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            'root.name',
            $pathResolver->resolve('name'),
        );
    }

    public function testRootAlias(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            'root',
            $pathResolver->resolve('*'),
        );
    }

    public function testRootEntityCast(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a0.gender',
            $pathResolver->resolve('(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).gender'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Customer root LEFT JOIN Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer _a0 WITH _a0.id = root.id',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    //
    // MANY TO ONE
    //

    public function testManyToOneEntity(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            'root.country',
            $pathResolver->resolve('country'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Customer root',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    public function testManyToOneEntityAlias(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a0',
            $pathResolver->resolve('country.*'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Customer root LEFT JOIN root.country _a0',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    public function testManyToOneEntityProperty(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a0.name',
            $pathResolver->resolve('country.name'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Customer root LEFT JOIN root.country _a0',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    public function testManyToOneEntityCast(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Order::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a1.gender',
            $pathResolver->resolve('customer(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).gender'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0 LEFT JOIN Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer _a1 WITH _a1.id = _a0.id',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    //
    // TWO LEVELS MANY TO ONE
    //

    public function testTwoLevelManyToOneEntity(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Order::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a0.country',
            $pathResolver->resolve('customer.country'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    public function testTwoLevelManyToOneEntityAlias(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Order::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a1',
            $pathResolver->resolve('customer.country.*'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0 LEFT JOIN _a0.country _a1',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    public function testTwoLevelManyToOneEntityProperty(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Order::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a1.name',
            $pathResolver->resolve('customer.country.name'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0 LEFT JOIN _a0.country _a1',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    //
    // ONE TO MANY
    //

    public function testOneToManyEntity(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            'root.orders',
            $pathResolver->resolve('orders'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Customer root',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    public function testOneToManyEntityAlias(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a0',
            $pathResolver->resolve('orders.*'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Customer root LEFT JOIN root.orders _a0',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }

    public function testOneToManyEntityProperty(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            '_a0.time',
            $pathResolver->resolve('orders.time'),
        );

        $this->assertEquals(
            'SELECT root.id FROM Rekalogika\Analytics\Tests\App\Entity\Customer root LEFT JOIN root.orders _a0',
            $pathResolver->getQueryBuilder()->getQuery()->getDQL(),
        );
    }
}
