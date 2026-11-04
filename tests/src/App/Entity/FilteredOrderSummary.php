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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\Filter\SourceCondition;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * The orders placed in 2024 or later, of the items priced 50 EUR or more, by
 * individual customers. The filter reads a property of the source entity, a
 * property of a related entity, and the type of a related entity.
 */
#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Order::class,
    label: new TranslatableMessage('Filtered orders'),
    filter: new CompositeExpression(CompositeExpression::TYPE_AND, [
        new Comparison('time', '>=', '2024-01-01'),
        new Comparison('item.price', '>=', 5000),
        new SourceCondition('[customer.*] INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer'),
    ]),
)]
class FilteredOrderSummary extends BaseSummary
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

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new IdentifierValue('customer.country'),
        label: new TranslatableMessage('Customer country'),
    )]
    private ?Country $customerCountry = null;

    //
    // measures
    //

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Count('id'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

    //
    // getters
    //

    public function getPartition(): Partition
    {
        return $this->partition;
    }

    public function getCustomerCountry(): ?Country
    {
        return $this->customerCountry;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}
