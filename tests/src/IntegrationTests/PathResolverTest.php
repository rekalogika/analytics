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

    public function testRelatedEntity(): void
    {
        $pathResolver = $this->createPathResolver(
            class: Customer::class,
            alias: 'root',
        );

        $this->assertEquals(
            'root.country',
            $pathResolver->resolve('country'),
        );
    }

    public function testRelatedEntityAlias(): void
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

    public function testRelatedEntityProperty(): void
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

    public function testRelatedEntityCast(): void
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
}
