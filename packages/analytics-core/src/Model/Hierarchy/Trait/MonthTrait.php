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
use Rekalogika\Analytics\Model\TimeInterval\Month;
use Rekalogika\Analytics\Model\TimeInterval\MonthOfYear;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Rekalogika\Analytics\ValueResolver\TimeBin;
use Rekalogika\Analytics\ValueResolver\TimeFormat;

trait MonthTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

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
