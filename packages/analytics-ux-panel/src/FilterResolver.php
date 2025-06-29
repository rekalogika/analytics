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

interface FilterResolver
{
    /**
     * Gets a suitable filter factory for the given dimension.
     */
    public function getFilterFactory(DimensionMetadata $dimension): FilterSpecification;
}
