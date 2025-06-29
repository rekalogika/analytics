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

namespace Rekalogika\Analytics\UX\PanelBundle;

final readonly class FilterSpecification
{
    /**
     * @param class-string<Filter> $filterClass
     */
    public function __construct(
        private string $filterClass,
        private ?object $filterOptions = null,
    ) {}

    /**
     * @return class-string<Filter>
     */
    public function getFilterClass(): string
    {
        return $this->filterClass;
    }

    public function getFilterOptions(): ?object
    {
        return $this->filterOptions;
    }
}
