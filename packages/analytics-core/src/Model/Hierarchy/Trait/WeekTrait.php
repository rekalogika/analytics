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
use Rekalogika\Analytics\Model\TimeInterval\Week;
use Rekalogika\Analytics\Model\TimeInterval\WeekOfMonth;
use Rekalogika\Analytics\Model\TimeInterval\WeekOfYear;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Rekalogika\Analytics\ValueResolver\TimeDimensionValueResolver;
use Rekalogika\Analytics\ValueResolver\TimeFormat;

trait WeekTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_week', nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Week),
    )]
    private ?Week $week = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: WeekOfYear::class)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekOfYear),
    )]
    private ?WeekOfYear $weekOfYear = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: WeekOfMonth::class)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekOfMonth),
    )]
    private ?WeekOfMonth $weekOfMonth = null;

    public function getWeek(): ?Week
    {
        return $this->week?->withTimeZone($this->getTimeZone());
    }

    public function getWeekOfYear(): ?WeekOfYear
    {
        return $this->weekOfYear;
    }

    public function getWeekOfMonth(): ?WeekOfMonth
    {
        return $this->weekOfMonth;
    }
}
