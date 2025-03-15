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

use Doctrine\ORM\Mapping\Column;
use Rekalogika\Analytics\Attribute\LevelProperty;
use Rekalogika\Analytics\DimensionValueResolver\TimeDimensionValueResolver;
use Rekalogika\Analytics\DimensionValueResolver\TimeFormat;
use Rekalogika\Analytics\TimeInterval\Month;
use Rekalogika\Analytics\TimeInterval\MonthOfYear;
use Rekalogika\Analytics\TimeInterval\Types\MonthOfYearType;
use Rekalogika\Analytics\TimeInterval\Types\MonthType;
use Rekalogika\Analytics\Util\TranslatableMessage;

trait MonthTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

    #[Column(type: MonthType::class, nullable: true)]
    #[LevelProperty(
        level: 400,
        label: new TranslatableMessage('Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Month),
    )]
    private ?Month $month = null;

    #[Column(type: MonthOfYearType::class, nullable: true)]
    #[LevelProperty(
        level: 400,
        label: new TranslatableMessage('Month of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::MonthOfYear),
    )]
    private ?MonthOfYear $monthOfYear = null;

    public function getMonth(): ?Month
    {
        return $this->month?->withTimeZone($this->getTimeZone());
    }

    public function getMonthOfYear(): ?MonthOfYear
    {
        return $this->monthOfYear?->withTimeZone($this->getTimeZone());
    }
}
