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
use Rekalogika\Analytics\AggregateFunction\Sum;
use Rekalogika\Analytics\Attribute as Analytics;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\DimensionValueResolver\TimeFormat;
use Rekalogika\Analytics\Model\Partition\SingleLevelIntegerPartition;
use Rekalogika\Analytics\Model\Summary;
use Rekalogika\Analytics\Model\TimeInterval\Date;
use Rekalogika\Analytics\Model\TimeInterval\DayOfMonth;
use Rekalogika\Analytics\Model\TimeInterval\DayOfWeek;
use Rekalogika\Analytics\Model\TimeInterval\DayOfYear;
use Rekalogika\Analytics\ValueResolver\DateToIntegerResolver;
use Rekalogika\Analytics\ValueResolver\TimeDimensionValueResolver;
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
    #[Analytics\Partition(new DateToIntegerResolver('date'))]
    private SingleLevelIntegerPartition $partition;

    //
    // dimensions
    //

    #[ORM\Column(type: 'rekalogika_analytics_date', nullable: true)]
    #[Analytics\Dimension(
        source: new TimeDimensionValueResolver(
            property: 'date',
            format: TimeFormat::Date,
            sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
            summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        ),
        label: new TranslatableMessage('Date', domain: 'rekalogika_analytics'),
        mandatory: true,
    )]
    private ?Date $date = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new TimeDimensionValueResolver(
            property: 'date',
            format: TimeFormat::DayOfWeek,
            sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
            summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        ),
        label: new TranslatableMessage('Day of Week', domain: 'rekalogika_analytics'),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new TimeDimensionValueResolver(
            property: 'date',
            format: TimeFormat::DayOfMonth,
            sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
            summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        ),
        label: new TranslatableMessage('Day of Month', domain: 'rekalogika_analytics'),
    )]
    private ?DayOfMonth $dayOfMonth = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Analytics\Dimension(
        source: new TimeDimensionValueResolver(
            property: 'date',
            format: TimeFormat::DayOfYear,
            sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
            summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        ),
        label: new TranslatableMessage('Day of Year', domain: 'rekalogika_analytics'),
    )]
    private ?DayOfYear $dayOfYear = null;

    #[ORM\Column(enumType: Gender::class, nullable: true)]
    #[Analytics\Dimension(
        source: 'gender',
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
        return $this->date;
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
