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
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineListenerTest extends KernelTestCase
{
    public function testPersist(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $customerSummary = new CustomerSummary();

        $this->expectException(\LogicException::class);
        $entityManager->persist($customerSummary);
    }

    public function testLoad(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->expectException(\LogicException::class);
        $entityManager->getRepository(CustomerSummary::class)->findAll();
    }
}
