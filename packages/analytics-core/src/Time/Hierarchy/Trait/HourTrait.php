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

use Doctrine\ORM\Mapping\Column;
use Rekalogika\Analytics\Contracts\Common\TranslatableMessage;
use Rekalogika\Analytics\Contracts\Context\HierarchyContext;
use Rekalogika\Analytics\Contracts\Metadata\LevelProperty;
use Rekalogika\Analytics\Time\Bin\Hour;
use Rekalogika\Analytics\Time\Bin\HourOfDay;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

trait HourTrait
{
    abstract private function getContext(): HierarchyContext;

    #[Column(
        type: TimeBinType::TypeHour,
        nullable: true,
    )]
    #[LevelProperty(
        level: 100,
        label: new TranslatableMessage('Hour'),
        valueResolver: new TimeBin(TimeBinType::Hour),
    )]
    private ?Hour $hour = null;

    #[Column(
        type: TimeBinType::TypeHourOfDay,
        nullable: true,
        enumType: HourOfDay::class,
    )]
    #[LevelProperty(
        level: 100,
        label: new TranslatableMessage('Hour of Day'),
        valueResolver: new TimeBin(TimeBinType::HourOfDay),
    )]
    private ?HourOfDay $hourOfDay = null;

    public function getHour(): ?Hour
    {
        return $this->getContext()->getUserValue(
            property: 'hour',
            rawValue: $this->hour,
            class: Hour::class,
        );
    }

    public function getHourOfDay(): ?HourOfDay
    {
        return $this->getContext()->getUserValue(
            property: 'hourOfDay',
            rawValue: $this->hourOfDay,
            class: HourOfDay::class,
        );
    }
}
