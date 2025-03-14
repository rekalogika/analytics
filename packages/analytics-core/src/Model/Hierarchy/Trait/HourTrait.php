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
use Rekalogika\Analytics\TimeDimensionHierarchy\Hour;
use Rekalogika\Analytics\TimeDimensionHierarchy\HourOfDay;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\HourOfDayType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\HourType;
use Rekalogika\Analytics\Util\TranslatableMessage;

trait HourTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

    #[Column(type: HourType::class, nullable: true)]
    #[LevelProperty(
        level: 100,
        label: new TranslatableMessage('Hour'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Hour),
    )]
    private ?Hour $hour = null;

    #[Column(type: HourOfDayType::class, nullable: true)]
    #[LevelProperty(
        level: 100,
        label: new TranslatableMessage('Hour of Day'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::HourOfDay),
    )]
    private ?HourOfDay $hourOfDay = null;

    public function getHour(): ?Hour
    {
        return $this->hour?->withTimeZone($this->getTimeZone());
    }

    public function getHourOfDay(): ?HourOfDay
    {
        return $this->hourOfDay?->withTimeZone($this->getTimeZone());
    }
}
