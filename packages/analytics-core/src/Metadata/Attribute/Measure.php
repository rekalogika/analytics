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

namespace Rekalogika\Analytics\Metadata\Attribute;

use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Measure
{
    /**
     * @param AggregateFunction $function
     */
    public function __construct(
        private AggregateFunction $function,
        private null|string|TranslatableInterface $label = null,
        private null|string|TranslatableInterface $unit = null,
        private bool $hidden = false,
    ) {}

    /**
     * @return AggregateFunction
     */
    public function getFunction(): AggregateFunction
    {
        return $this->function;
    }

    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }

    public function getUnit(): null|string|TranslatableInterface
    {
        return $this->unit;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
