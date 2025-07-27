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

use Rekalogika\Analytics\Contracts\MemberValuesManager;
use Rekalogika\Analytics\Tests\App\Entity\CustomerType;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MemberValuesManagerTest extends KernelTestCase
{
    public function testDoctrineEntity(): void
    {
        $memberValuesManager = self::getContainer()
            ->get(MemberValuesManager::class);

        $values = $memberValuesManager->getDistinctValues(
            class: OrderSummary::class,
            dimension: 'customerCountry',
            limit: 100,
        );

        $this->assertIsIterable($values);
        /** @psalm-suppress InvalidArgument */
        $values = iterator_to_array($values);
        $this->assertCount(6, $values);
    }

    public function testEnum(): void
    {
        $memberValuesManager = self::getContainer()
            ->get(MemberValuesManager::class);

        $values = $memberValuesManager->getDistinctValues(
            class: OrderSummary::class,
            dimension: 'customerType',
            limit: 100,
        );

        $this->assertIsIterable($values);
        /** @psalm-suppress InvalidArgument */
        $values = iterator_to_array($values);
        $this->assertEquals([
            'individual' => CustomerType::Individual,
            'organizational' => CustomerType::Organizational,
        ], $values);
    }
}
