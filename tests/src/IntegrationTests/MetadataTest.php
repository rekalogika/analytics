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

use Rekalogika\Analytics\Metadata\SourceMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MetadataTest extends KernelTestCase
{
    public function testSourceMetadataForOrder(): void
    {
        $sourceMetadataFactory = static::getContainer()
            ->get(SourceMetadataFactory::class);

        $orderMetadata = $sourceMetadataFactory->getSourceMetadata(Order::class);

        $this->assertEquals(Order::class, $orderMetadata->getClass());

        $this->assertEquals(
            [OrderSummary::class],
            $orderMetadata->getInvolvedSummaryClassesByChangedProperties(['customer']),
        );

        $this->assertEquals(
            [OrderSummary::class],
            $orderMetadata->getInvolvedSummaryClassesByChangedProperties(['customer', 'item']),
        );

        $this->assertEquals(
            [],
            $orderMetadata->getInvolvedSummaryClassesByChangedProperties(['foo']),
        );
    }

    public function testSourceMetadataForCustomer(): void
    {
        $sourceMetadataFactory = static::getContainer()
            ->get(SourceMetadataFactory::class);

        $customerMetadata = $sourceMetadataFactory->getSourceMetadata(Customer::class);

        $this->assertEquals(Customer::class, $customerMetadata->getClass());

        $this->assertEquals(
            [CustomerSummary::class],
            $customerMetadata->getInvolvedSummaryClassesByChangedProperties(['country']),
        );

        $this->assertEquals(
            [],
            $customerMetadata->getInvolvedSummaryClassesByChangedProperties(['foo']),
        );
    }

    public function testSourceMetadataForIndividualCustomer(): void
    {
        $sourceMetadataFactory = static::getContainer()
            ->get(SourceMetadataFactory::class);

        $metadata = $sourceMetadataFactory->getSourceMetadata(IndividualCustomer::class);

        $this->assertEquals(IndividualCustomer::class, $metadata->getClass());

        $this->assertEquals(
            [CustomerSummary::class],
            $metadata->getInvolvedSummaryClassesByChangedProperties(['country']),
        );
    }
}
