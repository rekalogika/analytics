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

use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Measure
{
    /**
     * @param AggregateFunction|non-empty-array<class-string,AggregateFunction> $function
     */
    public function __construct(
        private AggregateFunction|array $function,
        private null|string|TranslatableInterface $label = null,
        private null|string|TranslatableInterface $unit = null,
    ) {
        // if function is array, make sure all values are of the same class

        if (\is_array($this->function)) {
            $class = null;

            foreach ($this->function as $function) {
                if ($class === null) {
                    $class = $function::class;
                } elseif ($class !== $function::class) {
                    throw new InvalidArgumentException(\sprintf(
                        'All functions must be of the same class, previous function was "%s", "%s" given',
                        $class,
                        $function::class,
                    ));
                }
            }
        }
    }

    /**
     * @return AggregateFunction|non-empty-array<class-string,AggregateFunction>
     */
    public function getFunction(): AggregateFunction|array
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
}
