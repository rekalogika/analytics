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
use Rekalogika\Analytics\Time\Bin\Year;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

trait YearTrait
{
    abstract private function getContext(): HierarchyContext;

    #[Column(
        type: TimeBinType::TypeYear,
        nullable: true,
    )]
    #[LevelProperty(
        level: 600,
        label: new TranslatableMessage('Year'),
        valueResolver: new TimeBin(TimeBinType::Year),
    )]
    private ?int $year = null;

    public function getYear(): ?Year
    {
        return $this->getContext()->getUserValue(
            property: 'year',
            rawValue: $this->year,
            class: Year::class,
        );
    }
}
