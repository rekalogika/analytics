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
use Rekalogika\Analytics\Model\TimeBin\Hour;
use Rekalogika\Analytics\Model\TimeBin\HourOfDay;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Rekalogika\Analytics\ValueResolver\TimeBin;
use Rekalogika\Analytics\ValueResolver\TimeFormat;

trait HourTrait
{
    abstract private function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_hour', nullable: true)]
    #[LevelProperty(
        level: 100,
        label: new TranslatableMessage('Hour'),
        valueResolver: new TimeBin(TimeFormat::Hour),
    )]
    private ?Hour $hour = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: HourOfDay::class)]
    #[LevelProperty(
        level: 100,
        label: new TranslatableMessage('Hour of Day'),
        valueResolver: new TimeBin(TimeFormat::HourOfDay),
    )]
    private ?HourOfDay $hourOfDay = null;

    public function getHour(): ?Hour
    {
        return $this->hour?->withTimeZone($this->getTimeZone());
    }

    public function getHourOfDay(): ?HourOfDay
    {
        return $this->hourOfDay;
    }
}
