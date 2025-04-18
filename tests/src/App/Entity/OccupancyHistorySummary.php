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

namespace Rekalogika\Analytics\Tests\App\Entity;

use Doctrine\Common\Collections\Order as DoctrineOrder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\AggregateFunction\Sum;
use Rekalogika\Analytics\Attribute as Analytics;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Model\Hierarchy\DateOnlyDimensionHierarchy;
use Rekalogika\Analytics\Model\Partition\SingleLevelIntegerPartition;
use Rekalogika\Analytics\Model\Summary;
use Rekalogika\Analytics\ValueResolver\DateToIntegerResolver;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: OccupancyHistory::class,
    label: new TranslatableMessage('Occupancy History'),
)]
class OccupancyHistorySummary extends Summary
{
    //
    // partition
    //

    #[ORM\Embedded()]
    #[Analytics\Partition(new DateToIntegerResolver('date'))]
    private SingleLevelIntegerPartition $partition;

    //
    // dimensions
    //

    #[ORM\Embedded()]
    #[Analytics\Dimension(
        source: new PropertyValueResolver('date'),
        label: new TranslatableMessage('Date'),
        sourceTimeZone: new \DateTimeZone('Asia/Jakarta'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
        orderBy: DoctrineOrder::Ascending,
    )]
    private DateOnlyDimensionHierarchy $date;

    #[ORM\Column(enumType: Gender::class, nullable: true)]
    #[Analytics\Dimension(
        source: 'gender',
        label: new TranslatableMessage('Gender'),
    )]
    private ?Gender $gender = null;

    //
    // measures
    //

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Sum('count'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

    //
    // getters
    //

    public function getPartition(): Partition
    {
        return $this->partition;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function getDate(): DateOnlyDimensionHierarchy
    {
        return $this->date;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}
