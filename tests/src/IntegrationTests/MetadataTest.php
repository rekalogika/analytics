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

use Rekalogika\Analytics\Metadata\Attribute\Partition;
use Rekalogika\Analytics\Metadata\Attribute\PartitionKey;
use Rekalogika\Analytics\Metadata\Attribute\PartitionLevel;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderExplicitGroupingSetsSummary;
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

        $this->assertEqualsCanonicalizing(
            [OrderSummary::class, OrderExplicitGroupingSetsSummary::class],
            $orderMetadata->getInvolvedSummaryClassesByChangedProperties(['customer']),
        );

        $this->assertEqualsCanonicalizing(
            [OrderSummary::class, OrderExplicitGroupingSetsSummary::class],
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

    public function testPartitionMetadataAttributes(): void
    {
        $attributes = static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata(OrderSummary::class)
            ->getPartition()
            ->getAttributes();

        $this->assertTrue($attributes->hasAttribute(Partition::class));
        $this->assertFalse($attributes->hasAttribute(PartitionKey::class));
        $this->assertFalse($attributes->hasAttribute(PartitionLevel::class));
    }
}
