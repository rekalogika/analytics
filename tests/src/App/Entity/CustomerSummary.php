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
use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\PseudoMeasure\CoordinatesMeasure;
use Rekalogika\Analytics\Core\ValueResolver\CustomExpression;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Rekalogika\Analytics\Numeric\Bin\IndexedBin;
use Rekalogika\Analytics\Numeric\Binning\LinearBinning;
use Rekalogika\Analytics\Numeric\Binning\LogarithmicBinning;
use Rekalogika\Analytics\Numeric\ValueResolver\NumericBinValueResolver;
use Rekalogika\Analytics\Numeric\ValueResolver\RangeBinValueResolver;
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

    /**
     * Reads a property of a subclass through a cast of the root entity.
     */
    #[ORM\Column(enumType: Gender::class, nullable: true)]
    #[Analytics\Dimension(
        source: new CustomExpression('[(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).gender]'),
        label: new TranslatableMessage('Gender'),
        nullLabel: new TranslatableMessage('Unspecified'),
    )]
    private ?Gender $gender = null;

    /**
     * Bins with custom ranges and labels, defined by an enum.
     */
    #[ORM\Column(enumType: AgeGroup::class, nullable: true)]
    #[Analytics\Dimension(
        source: new RangeBinValueResolver(
            binClass: AgeGroup::class,
            input: new CustomExpression('[(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).age]'),
        ),
        label: new TranslatableMessage('Age group'),
        nullLabel: new TranslatableMessage('Unspecified'),
    )]
    private ?AgeGroup $ageGroup = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new NumericBinValueResolver(
            binning: new LinearBinning(width: 10, min: 20, max: 70),
            input: new CustomExpression('[(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).age]'),
        ),
        label: new TranslatableMessage('Age (linear bins)'),
        nullLabel: new TranslatableMessage('Unspecified'),
    )]
    private ?int $ageLinear = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new NumericBinValueResolver(
            binning: new LogarithmicBinning(base: 2, min: 16),
            input: new CustomExpression('[(Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer).age]'),
        ),
        label: new TranslatableMessage('Age (logarithmic bins)'),
        nullLabel: new TranslatableMessage('Unspecified'),
    )]
    private ?int $ageLogarithmic = null;

    //
    // measures
    //

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Count('id'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

    #[Analytics\Measure(
        function: new CoordinatesMeasure(),
        label: new TranslatableMessage('Actions'),
    )]
    private ?Coordinates $actions = null;  // @phpstan-ignore property.unusedType

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

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function getAgeGroup(): ?AgeGroup
    {
        return $this->ageGroup;
    }

    public function getAgeLinear(): ?IndexedBin
    {
        return $this->getContext()->getUserValue(
            property: 'ageLinear',
            class: IndexedBin::class,
        );
    }

    public function getAgeLogarithmic(): ?IndexedBin
    {
        return $this->getContext()->getUserValue(
            property: 'ageLogarithmic',
            class: IndexedBin::class,
        );
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getActions(): ?Coordinates
    {
        return $this->actions;
    }
}
