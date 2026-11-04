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

namespace Rekalogika\Analytics\Tests\App\Entity\InverseSide;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Rekalogika\Analytics\Tests\App\Entity\TestIntegerPartition;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Reads a property through the inverse side of a one-to-one association.
 */
#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Account::class,
    label: new TranslatableMessage('Accounts'),
)]
class AccountSummary extends BaseSummary
{
    #[ORM\Embedded()]
    #[Analytics\Partition(new IntegerValue('id'))]
    private TestIntegerPartition $partition;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Analytics\Dimension(
        source: new PropertyValue('profile.tier'),
        label: new TranslatableMessage('Tier'),
    )]
    private ?string $tier = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Count('id'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

    public function getPartition(): TestIntegerPartition
    {
        return $this->partition;
    }

    public function getTier(): ?string
    {
        return $this->tier;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}
