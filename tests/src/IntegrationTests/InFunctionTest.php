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
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InFunctionTest extends KernelTestCase
{
    public function testLiteralOptions(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $sql = $entityManager
            ->createQuery(\sprintf(
                "SELECT i.id FROM %s i WHERE REKALOGIKA_IN(i.id, 1, 2, 3) = TRUE AND REKALOGIKA_IN(i.name, 'a', 'b') = TRUE",
                Item::class,
            ))
            ->getSQL();

        $this->assertIsString($sql);
        $this->assertStringContainsString(' IN (1, 2, 3)', $sql);
        $this->assertStringContainsString(" IN ('a', 'b')", $sql);
    }
}
