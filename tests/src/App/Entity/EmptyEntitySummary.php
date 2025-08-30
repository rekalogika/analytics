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
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Rekalogika\Analytics\Uuid\Partition\UuidV7IntegerPartition;
use Rekalogika\Analytics\Uuid\ValueResolver\StringUuidToTruncatedInteger;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: EmptyEntity::class,
    label: new TranslatableMessage('Empty Entity'),
)]
class EmptyEntitySummary extends BaseSummary
{
    //
    // partition
    //

    #[ORM\Embedded()]
    #[Analytics\Partition(new StringUuidToTruncatedInteger('id'))]
    private UuidV7IntegerPartition $partition;

    //
    // dimensions
    //

    #[ORM\Column(type: Types::STRING)]
    #[Analytics\Dimension(
        source: new PropertyValue('name'),
        label: new TranslatableMessage('Name'),
        orderBy: DoctrineOrder::Ascending,
    )]
    private ?string $name;

    //
    // measures
    //

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Count('id'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

    //
    // getters
    //

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}
