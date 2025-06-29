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

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;

/**
 * Creates a filter instance based on the provided dimension metadata and input
 * array.
 *
 * @template-covariant T of Filter
 * @template O of object
 */
interface FilterFactory
{
    /**
     * @return class-string<T>
     */
    public static function getFilterClass(): string;

    /**
     * @return class-string<O>|null
     */
    public static function getOptionObjectClass(): ?string;

    /**
     * Instantate a filter instance based on the provided dimension metadata and
     * input array.
     *
     * @param array<string,mixed> $inputArray
     * @param O|null $options
     * @return T
     */
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter;
}
