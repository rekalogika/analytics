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

use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Summary
{
    /**
     * @param class-string $sourceClass
     * @param GroupingStrategy|null $groupingStrategy The strategy that
     * determines which grouping sets of the root dimensions are stored in the
     * summary table. If null, the summary stores the cube of all the root
     * dimensions, i.e. every combination of every root dimension.
     */
    public function __construct(
        private string $sourceClass,
        private null|string|TranslatableInterface $label = null,
        private ?GroupingStrategy $groupingStrategy = null,
    ) {}

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }

    public function getGroupingStrategy(): ?GroupingStrategy
    {
        return $this->groupingStrategy;
    }
}
