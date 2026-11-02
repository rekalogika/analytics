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
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SimpleQueryBuilderTest extends KernelTestCase
{
    public function testJoinsAfterCloneGoToTheClone(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $original = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: Order::class,
            alias: 'root',
        );

        $original->select('root.id');
        $customerName = $original->resolve('customer.name');

        $clone = clone $original;

        // a join made before the clone is reused under the same alias
        $this->assertSame($customerName, $clone->resolve('customer.name'));

        $itemName = $clone->resolve('item.name');
        $itemAlias = explode('.', $itemName)[0];

        $originalDql = $original->getQueryBuilder()->getDQL();
        $cloneDql = $clone->getQueryBuilder()->getDQL();

        $this->assertStringContainsString('root.customer', $originalDql);
        $this->assertStringNotContainsString('root.item', $originalDql);

        $this->assertStringContainsString('root.customer', $cloneDql);
        $this->assertStringContainsString('root.item ' . $itemAlias, $cloneDql);
    }
}
