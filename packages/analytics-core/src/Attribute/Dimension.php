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

namespace Rekalogika\Analytics\Attribute;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Dimension
{
    /**
     * @param null|string|ValueResolver $source
     * @param Order|array<string,Order> $orderBy
     */
    public function __construct(
        private null|string|ValueResolver $source = null,
        private null|string|TranslatableInterface $label = null,
        private \DateTimeZone $sourceTimeZone = new \DateTimeZone('UTC'),
        private \DateTimeZone $summaryTimeZone = new \DateTimeZone('UTC'),
        private Order|array $orderBy = Order::Ascending,
        private null|string|TranslatableInterface $nullLabel = null,
        private bool $mandatory = false,
    ) {}

    /**
     * @return null|string|ValueResolver
     */
    public function getSource(): null|string|ValueResolver
    {
        return $this->source;
    }

    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }

    public function getSourceTimeZone(): \DateTimeZone
    {
        return $this->sourceTimeZone;
    }

    public function getSummaryTimeZone(): \DateTimeZone
    {
        return $this->summaryTimeZone;
    }

    /**
     * @return Order|array<string,Order>
     */
    public function getOrderBy(): Order|array
    {
        return $this->orderBy;
    }

    public function getNullLabel(): null|string|TranslatableInterface
    {
        return $this->nullLabel;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }
}
