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

use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MetadataTest extends KernelTestCase
{
    public function testSourceMetadata(): void
    {
        $summaryMetadataFactory = static::getContainer()
            ->get(SummaryMetadataFactory::class);

        $orderMetadata = $summaryMetadataFactory->getSourceMetadata(Order::class);

        $this->assertEquals(Order::class, $orderMetadata->getClass());

        $this->assertEquals(
            [OrderSummary::class],
            $orderMetadata->getInvolvedSummaryClasses(['customer']),
        );

        $this->assertEquals(
            [OrderSummary::class],
            $orderMetadata->getInvolvedSummaryClasses(['customer', 'item']),
        );

        $this->assertEquals(
            [],
            $orderMetadata->getInvolvedSummaryClasses(['foo']),
        );
    }
}
