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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\AggregateFunction\Count;
use Rekalogika\Analytics\Attribute as Analytics;
use Rekalogika\Analytics\Model\Partition\UuidV7IntegerPartition;
use Rekalogika\Analytics\ValueResolver\EntityValueResolver;
use Rekalogika\Analytics\ValueResolver\UuidToTruncatedIntegerResolver;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Customer::class,
    label: new TranslatableMessage('Customers'),
)]
class CustomerSummary
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    //
    // partition & groupings
    //

    #[ORM\Embedded()]
    #[Analytics\Partition(new UuidToTruncatedIntegerResolver('id'))]
    private UuidV7IntegerPartition $partition;

    #[ORM\Column]
    #[Analytics\Groupings]
    private string $groupings;

    //
    // dimensions
    //

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new EntityValueResolver('country'),
        label: new TranslatableMessage('Country'),
    )]
    // @phpstan-ignore property.onlyRead
    private ?Country $country = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new EntityValueResolver('country.region'),
        label: new TranslatableMessage('Region'),
    )]
    private ?Region $region = null;

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

    public function getPartition(): UuidV7IntegerPartition
    {
        return $this->partition;
    }

    public function getGroupings(): ?string
    {
        return $this->groupings;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}
