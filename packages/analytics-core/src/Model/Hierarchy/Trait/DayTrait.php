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

namespace Rekalogika\Analytics\Model\Hierarchy\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Rekalogika\Analytics\Attribute\LevelProperty;
use Rekalogika\Analytics\DimensionValueResolver\TimeDimensionValueResolver;
use Rekalogika\Analytics\DimensionValueResolver\TimeFormat;
use Rekalogika\Analytics\TimeInterval\Date;
use Rekalogika\Analytics\TimeInterval\DayOfMonth;
use Rekalogika\Analytics\TimeInterval\DayOfWeek;
use Rekalogika\Analytics\TimeInterval\DayOfYear;
use Rekalogika\Analytics\TimeInterval\WeekDate;
use Rekalogika\Analytics\Util\TranslatableMessage;

trait DayTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_date', nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Date'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Date),
    )]
    private ?Date $date = null;

    #[Column(type: 'rekalogika_analytics_week_date', nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Week Date'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekDate),
    )]
    private ?WeekDate $weekDate = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: DayOfWeek::class)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Week'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfWeek),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: DayOfMonth::class)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfMonth),
    )]
    private ?DayOfMonth $dayOfMonth = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: DayOfYear::class)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfYear),
    )]
    private ?DayOfYear $dayOfYear = null;

    public function getDate(): ?Date
    {
        return $this->date?->withTimeZone($this->getTimeZone());
    }

    public function getWeekDate(): ?WeekDate
    {
        return $this->weekDate?->withTimeZone($this->getTimeZone());
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
}
