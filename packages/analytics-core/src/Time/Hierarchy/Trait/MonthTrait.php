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

namespace Rekalogika\Analytics\Time\Hierarchy\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Rekalogika\Analytics\Contracts\Common\TranslatableMessage;
use Rekalogika\Analytics\Contracts\Metadata\LevelProperty;
use Rekalogika\Analytics\Time\Bin\Month;
use Rekalogika\Analytics\Time\Bin\MonthOfYear;
use Rekalogika\Analytics\Time\TimeFormat;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

trait MonthTrait
{
    abstract private function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_month', nullable: true)]
    #[LevelProperty(
        level: 400,
        label: new TranslatableMessage('Month'),
        valueResolver: new TimeBin(TimeFormat::Month),
    )]
    private ?Month $month = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: MonthOfYear::class)]
    #[LevelProperty(
        level: 400,
        label: new TranslatableMessage('Month of Year'),
        valueResolver: new TimeBin(TimeFormat::MonthOfYear),
    )]
    private ?MonthOfYear $monthOfYear = null;

    public function getMonth(): ?Month
    {
        return $this->month?->withTimeZone($this->getTimeZone());
    }

    public function getMonthOfYear(): ?MonthOfYear
    {
        return $this->monthOfYear;
    }
}
