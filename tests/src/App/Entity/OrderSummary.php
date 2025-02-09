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
use Rekalogika\Analytics\AggregateFunction\Count;
use Rekalogika\Analytics\AggregateFunction\Sum;
use Rekalogika\Analytics\Attribute as Analytics;
use Rekalogika\Analytics\HasQueryBuilderModifier;
use Rekalogika\Analytics\Model\Hierarchy\TimeDimensionHierarchy;
use Rekalogika\Analytics\Model\Summary;
use Rekalogika\Analytics\Partition;
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
    // @phpstan-ignore property.onlyRead
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
        source: new CustomDQLValueResolver(
            dql: "
                CASE
                    WHEN %s INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer
                    THEN 'individual'

                    WHEN %s INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\OrganizationalCustomer
                    THEN 'organizational'

                    ELSE REKALOGIKA_NULL()
                END
            ",
            fields: [
                '*customer',
                '*customer',
            ],
        ),
        label: new TranslatableMessage('Customer Type'),
    )]
    private ?CustomerType $customerType = null;

    #[ORM\Column(enumType: Gender::class, nullable: true)]
    #[Analytics\Dimension(
        source: new CustomDQLValueResolver(
            dql: "
                CASE
                    WHEN %s INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer
                    THEN %s

                    ELSE REKALOGIKA_NULL()
                END
            ",
            fields: [
                '*customer',
                'customer(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).gender',
            ],
        ),
        label: new TranslatableMessage('Customer Gender'),
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
    )]
    private ?Country $itemCountryOfOrigin = null;

    //
    // measures
    //

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Sum('item.price'),
        label: new TranslatableMessage('Price'),
    )]
    private ?int $price = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Count('id'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

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

    public function getCount(): ?int
    {
        return $this->count;
    }
}
