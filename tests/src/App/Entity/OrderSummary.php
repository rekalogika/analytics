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

namespace Rekalogika\Analytics\Tests\App\Entity;

use Brick\Money\Money;
use Doctrine\Common\Collections\Order as DoctrineOrder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\AggregateFunction\Average;
use Rekalogika\Analytics\AggregateFunction\Count;
use Rekalogika\Analytics\AggregateFunction\CountDistinct;
use Rekalogika\Analytics\AggregateFunction\Max;
use Rekalogika\Analytics\AggregateFunction\Min;
use Rekalogika\Analytics\AggregateFunction\Range;
use Rekalogika\Analytics\AggregateFunction\Sum;
use Rekalogika\Analytics\Attribute as Analytics;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Model\Hierarchy\TimeDimensionHierarchy;
use Rekalogika\Analytics\Model\Summary;
use Rekalogika\Analytics\ValueResolver\CustomDQLValueResolver;
use Rekalogika\Analytics\ValueResolver\EntityValueResolver;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Order::class,
    label: new TranslatableMessage('Orders'),
)]
class OrderSummary extends Summary implements HasQueryBuilderModifier
{
    //
    // partition
    //

    #[ORM\Embedded()]
    #[Analytics\Partition(new PropertyValueResolver('id'))]
    private TestIntegerPartition $partition;

    //
    // dimensions
    //

    #[ORM\Embedded()]
    #[Analytics\Dimension(
        source: new PropertyValueResolver('time'),
        label: new TranslatableMessage('Placed Time'),
        sourceTimeZone: new \DateTimeZone('UTC'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        orderBy: DoctrineOrder::Ascending,
    )]
    private TimeDimensionHierarchy $time;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new EntityValueResolver('customer.country'),
        label: new TranslatableMessage('Customer country'),
        orderBy: ['name' => DoctrineOrder::Ascending],
    )]
    private ?Country $customerCountry = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new EntityValueResolver('customer.country.region'),
        label: new TranslatableMessage('Customer Region'),
        orderBy: ['name' => DoctrineOrder::Ascending],
    )]
    private ?Region $customerRegion = null;

    #[ORM\Column(enumType: CustomerType::class, nullable: true)]
    #[Analytics\Dimension(
        source: new CustomDQLValueResolver("
            CASE
                WHEN [customer.*] INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer
                THEN 'individual'

                WHEN [customer.*] INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\OrganizationalCustomer
                THEN 'organizational'

                ELSE NULLIF('a','a')
            END
        "),
        label: new TranslatableMessage('Customer Type'),
    )]
    private ?CustomerType $customerType = null;

    #[ORM\Column(enumType: Gender::class, nullable: true)]
    #[Analytics\Dimension(
        source: new CustomDQLValueResolver("
            CASE
                WHEN [customer.*] INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer
                THEN [customer(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).gender]

                ELSE NULLIF('a','a')
            END
        "),
        label: new TranslatableMessage('Customer Gender'),
        nullLabel: new TranslatableMessage('Unspecified'),
    )]
    private ?Gender $customerGender = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new EntityValueResolver('item.category'),
        label: new TranslatableMessage('Item Category'),
    )]
    private ?Category $itemCategory = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new EntityValueResolver('item.countryOfOrigin'),
        label: new TranslatableMessage('Item Country of Origin'),
        orderBy: ['name' => DoctrineOrder::Ascending],
    )]
    private ?Country $itemCountryOfOrigin = null;

    //
    // measures
    //

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Sum('item.price'),
        label: new TranslatableMessage('Price'),
        unit: new TranslatableMessage('Monetary Value (EUR)'),
    )]
    private ?int $price = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Min('item.price'),
        label: new TranslatableMessage('Minimum Price'),
        unit: new TranslatableMessage('Monetary Value (EUR)'),
    )]
    private ?int $minPrice = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Max('item.price'),
        label: new TranslatableMessage('Maximum Price'),
        unit: new TranslatableMessage('Monetary Value (EUR)'),
    )]
    private ?int $maxPrice = null;

    #[Analytics\Measure(
        function: new Range(
            minProperty: 'minPrice',
            maxProperty: 'maxPrice',
        ),
        label: new TranslatableMessage('Price Range'),
        unit: new TranslatableMessage('Monetary Value (EUR)'),
    )]
    private ?int $priceRange = null;  // @phpstan-ignore property.unusedType


    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Count('id'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

    #[ORM\Column(type: 'rekalogika_hll')]
    #[Analytics\Measure(
        function: new CountDistinct(new EntityValueResolver('customer')),
        label: new TranslatableMessage('Unique customers'),
    )]
    private ?int $uniqueCustomers = null;

    #[Analytics\Measure(
        function: new Average(
            sumProperty: 'price',
            countProperty: 'uniqueCustomers',
        ),
        label: new TranslatableMessage('Average spending per customer'),
    )]
    private ?int $averageSpendingPerCustomer = null; // @phpstan-ignore property.unusedType

    #[Analytics\Measure(
        function: new Average(
            sumProperty: 'price',
            countProperty: 'count',
        ),
        label: new TranslatableMessage('Average order value'),
    )]
    private ?int $averageOrderValue = null; // @phpstan-ignore property.unusedType


    #[\Override]
    public static function modifyQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->andWhere('root.id > 10');
    }

    //
    // getters
    //

    public function getPartition(): Partition
    {
        return $this->partition;
    }

    public function getTime(): TimeDimensionHierarchy
    {
        return $this->time;
    }

    public function getCustomerCountry(): ?Country
    {
        return $this->customerCountry;
    }

    public function getCustomerRegion(): ?Region
    {
        return $this->customerRegion;
    }

    public function getCustomerType(): ?CustomerType
    {
        return $this->customerType;
    }

    public function getCustomerGender(): ?Gender
    {
        return $this->customerGender;
    }

    public function getItemCategory(): ?Category
    {
        return $this->itemCategory;
    }

    public function getItemCountryOfOrigin(): ?Country
    {
        return $this->itemCountryOfOrigin;
    }

    public function getPrice(): ?Money
    {
        if ($this->price === null) {
            return null;
        }

        return Money::ofMinor($this->price, 'EUR');
    }

    public function getMinPrice(): ?Money
    {
        if ($this->minPrice === null) {
            return null;
        }

        return Money::ofMinor($this->minPrice, 'EUR');
    }

    public function getMaxPrice(): ?Money
    {
        if ($this->maxPrice === null) {
            return null;
        }

        return Money::ofMinor($this->maxPrice, 'EUR');
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getUniqueCustomers(): ?int
    {
        return $this->uniqueCustomers;
    }

    public function getAverageSpendingPerCustomer(): ?Money
    {
        if ($this->averageSpendingPerCustomer === null) {
            return null;
        }

        return Money::ofMinor($this->averageSpendingPerCustomer, 'EUR');
    }

    public function getAverageOrderValue(): ?Money
    {
        if ($this->averageOrderValue === null) {
            return null;
        }

        return Money::ofMinor($this->averageOrderValue, 'EUR');
    }

    public function getPriceRange(): ?Money
    {
        if ($this->priceRange === null) {
            return null;
        }

        return Money::ofMinor($this->priceRange, 'EUR');
    }
}
