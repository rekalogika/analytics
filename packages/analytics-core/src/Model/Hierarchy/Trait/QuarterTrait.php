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
use Rekalogika\Analytics\DimensionValueResolver\TimeDimensionValueResolver;
use Rekalogika\Analytics\DimensionValueResolver\TimeFormat;
use Rekalogika\Analytics\Model\TimeInterval\Quarter;
use Rekalogika\Analytics\Model\TimeInterval\QuarterOfYear;
use Rekalogika\Analytics\Util\TranslatableMessage;

trait QuarterTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

    #[Column(type: 'rekalogika_analytics_quarter', nullable: true)]
    #[LevelProperty(
        level: 500,
        label: new TranslatableMessage('Quarter'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Quarter),
    )]
    private ?Quarter $quarter = null;

    #[Column(type: Types::SMALLINT, nullable: true, enumType: QuarterOfYear::class)]
    #[LevelProperty(
        level: 500,
        label: new TranslatableMessage('Quarter of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::QuarterOfYear),
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
