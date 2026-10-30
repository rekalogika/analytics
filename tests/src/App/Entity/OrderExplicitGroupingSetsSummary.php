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

use Doctrine\Common\Collections\Order as DoctrineOrder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\AggregateFunction\Sum;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\GroupingStrategy\ExplicitGroupingSetsStrategy;
use Rekalogika\Analytics\Core\ValueResolver\CustomExpression;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Rekalogika\Analytics\Time\Dimension\Group\TimeGroup;
use Rekalogika\Analytics\Time\Metadata\TimeProperties;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Same source and dimensions as OrderSummary, but stores only the explicitly
 * listed grouping sets instead of the cube of every dimension.
 */
#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Order::class,
    label: new TranslatableMessage('Orders (explicit grouping sets)'),
    groupingStrategy: new ExplicitGroupingSetsStrategy([
        ['time', 'customerCountry', 'customerType', 'itemCategory'],
        ['customerRegion'],
        ['customerGender', 'customerType'],
    ]),
)]
class OrderExplicitGroupingSetsSummary extends BaseSummary implements HasQueryBuilderModifier
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
        orderBy: DoctrineOrder::Ascending,
    )]
    #[TimeProperties(
        sourceTimeZone: new \DateTimeZone('UTC'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
    )]
    private TimeGroup $time;

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

    public function getTime(): TimeGroup
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

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}
