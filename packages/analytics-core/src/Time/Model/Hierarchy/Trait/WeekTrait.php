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

namespace Rekalogika\Analytics\Time\Model\Hierarchy\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Rekalogika\Analytics\Attribute\LevelProperty;
use Rekalogika\Analytics\Time\Model\TimeBin\Week;
use Rekalogika\Analytics\Time\Model\TimeBin\WeekOfMonth;
use Rekalogika\Analytics\Time\Model\TimeBin\WeekOfYear;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Rekalogika\Analytics\ValueResolver\TimeBin;
use Rekalogika\Analytics\ValueResolver\TimeFormat;

trait WeekTrait
{
    abstract private function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_week', nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week'),
        valueResolver: new TimeBin(TimeFormat::Week),
    )]
    private ?Week $week = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: WeekOfYear::class)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Year'),
        valueResolver: new TimeBin(TimeFormat::WeekOfYear),
    )]
    private ?WeekOfYear $weekOfYear = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: WeekOfMonth::class)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Month'),
        valueResolver: new TimeBin(TimeFormat::WeekOfMonth),
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
