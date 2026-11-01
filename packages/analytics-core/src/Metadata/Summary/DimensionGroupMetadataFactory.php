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

namespace Rekalogika\Analytics\Metadata\Summary;

/**
 * Creates the metadata of dimension group classes.
 */
interface DimensionGroupMetadataFactory
{
    /**
     * Returns the metadata of the dimension group class.
     *
     * @param class-string $class A class having the `DimensionGroup`
     * attribute.
     */
    public function getDimensionGroupMetadata(
        string $class,
    ): DimensionGroupMetadata;

    /**
     * Returns a copy of this factory that uses the given factory to create
     * the metadata of the dimensions inside a group. This breaks the circular
     * dependency between the two factories: the dimension metadata factory
     * calls this with itself.
     */
    public function with(
        DimensionMetadataFactory $dimensionMetadataFactory,
    ): static;
}
