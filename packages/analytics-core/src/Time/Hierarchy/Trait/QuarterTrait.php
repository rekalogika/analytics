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
use Rekalogika\Analytics\Time\Bin\Quarter;
use Rekalogika\Analytics\Time\Bin\QuarterOfYear;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

trait QuarterTrait
{
    abstract private function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_quarter', nullable: true)]
    #[LevelProperty(
        level: 500,
        label: new TranslatableMessage('Quarter'),
        valueResolver: new TimeBin(TimeBinType::Quarter),
    )]
    private ?Quarter $quarter = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: QuarterOfYear::class)]
    #[LevelProperty(
        level: 500,
        label: new TranslatableMessage('Quarter of Year'),
        valueResolver: new TimeBin(TimeBinType::QuarterOfYear),
    )]
    private ?QuarterOfYear $quarterOfYear = null;

    public function getQuarter(): ?Quarter
    {
        return $this->quarter?->withTimeZone($this->getTimeZone());
    }

    public function getQuarterOfYear(): ?QuarterOfYear
    {
        return $this->quarterOfYear;
    }
}
