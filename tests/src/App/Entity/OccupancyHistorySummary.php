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
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Core\AggregateFunction\Sum;
use Rekalogika\Analytics\Core\Entity\Summary;
use Rekalogika\Analytics\Core\Metadata as Analytics;
use Rekalogika\Analytics\Core\Partition\SingleLevelIntegerPartition;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\Bin\DayOfMonth;
use Rekalogika\Analytics\Time\Bin\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\DayOfYear;
use Rekalogika\Analytics\Time\Metadata\TimeProperties;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\DateToInteger;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: OccupancyHistory::class,
    label: new TranslatableMessage('Occupancy History'),
)]
class OccupancyHistorySummary extends Summary
{
    //
    // partition
    //

    #[ORM\Embedded()]
    #[Analytics\Partition(new DateToInteger('date'))]
    private SingleLevelIntegerPartition $partition;

    //
    // dimensions
    //

    #[ORM\Column(type: TimeBinType::TypeDate, nullable: true)]
    #[Analytics\Dimension(
        source: new TimeBin(
            input: new PropertyValue('date'),
            type: TimeBinType::Date,
        ),
        label: new TranslatableMessage('Date', domain: 'rekalogika_analytics'),
        mandatory: true,
    )]
    #[TimeProperties(
        sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
    )]
    private ?int $date = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new TimeBin(
            input: new PropertyValue('date'),
            type: TimeBinType::DayOfWeek,
        ),
        label: new TranslatableMessage('Day of Week', domain: 'rekalogika_analytics'),
    )]
    #[TimeProperties(
        sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new TimeBin(
            input: new PropertyValue('date'),
            type: TimeBinType::DayOfMonth,
        ),
        label: new TranslatableMessage('Day of Month', domain: 'rekalogika_analytics'),
    )]
    #[TimeProperties(
        sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
    )]
    private ?DayOfMonth $dayOfMonth = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new TimeBin(
            input: new PropertyValue('date'),
            type: TimeBinType::DayOfYear,
        ),
        label: new TranslatableMessage('Day of Year', domain: 'rekalogika_analytics'),
    )]
    #[TimeProperties(
        sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
    )]
    private ?DayOfYear $dayOfYear = null;

    #[ORM\Column(enumType: Gender::class, nullable: true)]
    #[Analytics\Dimension(
        source: new PropertyValue('gender'),
        label: new TranslatableMessage('Gender'),
    )]
    private ?Gender $gender = null;

    //
    // measures
    //

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Sum('count'),
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

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function getDate(): ?Date
    {
        return $this->getContext()->getUserValue(
            property: 'date',
            rawValue: $this->date,
            class: Date::class,
        );
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function getDayOfMonth(): ?DayOfMonth
    {
        return $this->dayOfMonth;
    }

    public function getDayOfYear(): ?DayOfYear
    {
        return $this->dayOfYear;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}
