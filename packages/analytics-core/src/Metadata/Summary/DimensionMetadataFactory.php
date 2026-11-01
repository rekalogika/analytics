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
 * Creates the metadata of a single dimension property, whether it is in a
 * summary class or in a dimension group.
 */
interface DimensionMetadataFactory
{
    /**
     * Creates the metadata of the dimension property. The metadata is created
     * standalone, i.e. without a parent or a summary; those are attached
     * later by whoever assembles the tree.
     *
     * @param class-string $class The summary or dimension group class.
     * @param string $property The property having the `Dimension` attribute.
     */
    public function createDimensionMetadata(
        string $class,
        string $property,
    ): DimensionMetadata;
}
