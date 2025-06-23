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

namespace Rekalogika\Analytics\Engine\SummaryManager\Groupings;

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;

final class Selector
{
    public function __construct(
        private DimensionMetadata $dimensionMetadata,
        private string $selectedPropertyName,
    ) {}
}
