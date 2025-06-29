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

namespace Rekalogika\Analytics\UX\PanelBundle\FilterResolver;

use Rekalogika\Analytics\Frontend\Formatter\Unsupported;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;
use Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange\DateRangeFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\TimeBin\TimeBinFilter;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;

final readonly class TimeBinFilterResolver implements FilterResolver
{
    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): string
    {
        $typeClass = $dimension->getTypeClass();
        $valueResolver = $dimension->getValueResolver();

        if (!$valueResolver instanceof TimeBinValueResolver) {
            throw new Unsupported();
        }

        $typeClass = $valueResolver->getTypeClass();

        if (is_a($typeClass, Date::class, true)) {
            return DateRangeFilter::class;
        } else {
            return TimeBinFilter::class;
        }
    }
}
