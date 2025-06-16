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
use Rekalogika\Analytics\Contracts\Metadata as Analytics;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Core\AggregateFunction\Average;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\AggregateFunction\Max;
use Rekalogika\Analytics\Core\AggregateFunction\Min;
use Rekalogika\Analytics\Core\AggregateFunction\Range;
use Rekalogika\Analytics\Core\AggregateFunction\StdDev;
use Rekalogika\Analytics\Core\AggregateFunction\Sum;
use Rekalogika\Analytics\Core\AggregateFunction\SumSquare;
use Rekalogika\Analytics\Core\Entity\Summary;
use Rekalogika\Analytics\Core\ValueResolver\CustomExpression;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\PostgreSQLHll\AggregateFunction\CountDistinct;
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\Hierarchy\TimeDimensionHierarchy;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;
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
    #[Analytics\Partition(new IntegerValue('id'))]
    private TestIntegerPartition $partition;

    //
    // dimensions
    //

    #[ORM\Embedded()]
    #[Analytics\Dimension(
        source: new PropertyValue('time'),
        label: new TranslatableMessage('Placed Time'),
        sourceTimeZone: new \DateTimeZone('UTC'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        orderBy: DoctrineOrder::Ascending,
    )]
    private TimeDimensionHierarchy $time;

    #[ORM\Column(type: 'rekalogika_analytics_date', nullable: true)]
    #[Analytics\Dimension(
        source: new TimeBin(
            format: TimeBinType::Date,
            input: new PropertyValue('shipped'),
        ),
        label: new TranslatableMessage('Date shipped'),
        sourceTimeZone: new \DateTimeZone('UTC'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        orderBy: DoctrineOrder::Ascending,
    )]
    private ?Date $shipped = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new IdentifierValue('customer.country'),
        label: new TranslatableMessage('Customer country'),
        orderBy: ['name' => DoctrineOrder::Ascending],
    )]
    private ?Country $customerCountry = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new IdentifierValue('customer.country.region'),
        label: new TranslatableMessage('Customer Region'),
        orderBy: ['name' => DoctrineOrder::Ascending],
    )]
    private ?Region $customerRegion = null;

    #[ORM\Column(enumType: CustomerType::class, nullable: true)]
    #[Analytics\Dimension(
        source: new CustomExpression("
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
        source: new CustomExpression("
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
        source: new IdentifierValue('item.category'),
        label: new TranslatableMessage('Item Category'),
    )]
    private ?Category $itemCategory = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new IdentifierValue('item.countryOfOrigin'),
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

    #[ORM\Column(type: Types::FLOAT)]
    #[Analytics\Measure(
        function: new SumSquare('item.price'),
        label: new TranslatableMessage('Price Sum Square'),
        hidden: true,
    )]
    private ?float $priceSumSquare = null;

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
        function: new CountDistinct(new IdentifierValue('customer')),
        label: new TranslatableMessage('Unique customers'),
    )]
    private ?int $uniqueCustomers = null;

    #[Analytics\Measure(
        function: new Average(
            sumProperty: 'price',
            countProperty: 'uniqueCustomers',
        ),
        label: new TranslatableMessage('Average spending per customer'),
        unit: new TranslatableMessage('Monetary Value (EUR)'),
    )]
    private ?int $averageSpendingPerCustomer = null; // @phpstan-ignore property.unusedType

    #[Analytics\Measure(
        function: new Average(
            sumProperty: 'price',
            countProperty: 'count',
        ),
        label: new TranslatableMessage('Average order value'),
        unit: new TranslatableMessage('Monetary Value (EUR)'),
    )]
    private ?int $averageOrderValue = null; // @phpstan-ignore property.unusedType

    #[Analytics\Measure(
        function: new StdDev(
            sumSquareProperty: 'priceSumSquare',
            countProperty: 'count',
            sumProperty: 'price',
        ),
        label: new TranslatableMessage('Price StdDev'),
        unit: new TranslatableMessage('Monetary Value (EUR)'),
    )]
    private ?int $priceStdDev = null;  // @phpstan-ignore property.unusedType


    #[\Override]
    public static function modifyQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder
            ->andWhere('root.id > :minId')
            ->setParameter('minId', 10);
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

    public function getShipped(): ?Date
    {
        return $this->shipped;
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

    public function getPriceStdDev(): ?Money
    {
        if ($this->priceStdDev === null) {
            return null;
        }

        return Money::ofMinor($this->priceStdDev, 'EUR');
    }

    public function getPriceSumSquare(): ?float
    {
        return $this->priceSumSquare;
    }
}
