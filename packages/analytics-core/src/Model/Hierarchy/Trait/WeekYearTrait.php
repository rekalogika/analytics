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
use Rekalogika\Analytics\Model\TimeBin\WeekYear;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Rekalogika\Analytics\ValueResolver\TimeBin;
use Rekalogika\Analytics\ValueResolver\TimeFormat;

trait WeekYearTrait
{
    abstract private function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_week_year', nullable: true)]
    #[LevelProperty(
        level: 700,
        label: new TranslatableMessage('Week Year'),
        valueResolver: new TimeBin(TimeFormat::WeekYear),
    )]
    private ?WeekYear $weekYear = null;

    public function getWeekYear(): ?WeekYear
    {
        return $this->weekYear?->withTimeZone($this->getTimeZone());
    }
}
