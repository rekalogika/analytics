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
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Rekalogika\Analytics\Uuid\Partition\UuidV7IntegerPartition;
use Rekalogika\Analytics\Uuid\ValueResolver\StringUuidToTruncatedInteger;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Customer::class,
    label: new TranslatableMessage('Customers'),
)]
class CustomerSummary extends BaseSummary
{
    //
    // partition
    //

    #[ORM\Embedded()]
    #[Analytics\Partition(new StringUuidToTruncatedInteger('id'))]
    private UuidV7IntegerPartition $partition;

    //
    // dimensions
    //

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new IdentifierValue('country'),
        label: new TranslatableMessage('Country'),
    )]
    // @phpstan-ignore property.onlyRead
    private ?Country $country = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new IdentifierValue('country.region'),
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
