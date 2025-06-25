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

namespace Rekalogika\Analytics\Core\Metadata;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Dimension
{
    /**
     * @param Order|array<string,Order> $orderBy
     */
    public function __construct(
        private ValueResolver $source,
        private null|string|TranslatableInterface $label = null,
        private Order|array $orderBy = Order::Ascending,
        private null|string|TranslatableInterface $nullLabel = null,
        private bool $mandatory = false,
        private bool $hidden = false,
    ) {}

    /**
     * @return ValueResolver
     *
     */
    public function getSource(): ValueResolver
    {
        return $this->source;
    }

    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
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

    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
