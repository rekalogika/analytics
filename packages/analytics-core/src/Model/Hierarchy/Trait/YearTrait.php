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
use Rekalogika\Analytics\Model\TimeInterval\Year;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Rekalogika\Analytics\ValueResolver\TimeBin;
use Rekalogika\Analytics\ValueResolver\TimeFormat;

trait YearTrait
{
    abstract private function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_year', nullable: true)]
    #[LevelProperty(
        level: 600,
        label: new TranslatableMessage('Year'),
        valueResolver: new TimeBin(TimeFormat::Year),
    )]
    private ?Year $year = null;

    public function getYear(): ?Year
    {
        return $this->year?->withTimeZone($this->getTimeZone());
    }
}
