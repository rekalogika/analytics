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

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\DimensionNotSupportedByFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilter;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterSpecification;

final readonly class EnumFilterResolver implements FilterResolver
{
    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): FilterSpecification
    {
        $typeClass = $dimension->getTypeClass();

        if ($typeClass !== null && enum_exists($typeClass)) {
            return new FilterSpecification(ChoiceFilter::class);
        }

        throw new DimensionNotSupportedByFilter();
    }
}
