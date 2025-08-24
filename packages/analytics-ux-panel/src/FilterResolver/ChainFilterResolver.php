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
use Rekalogika\Analytics\UX\PanelBundle\Filter\Null\NullFilter;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterSpecification;

final readonly class ChainFilterResolver implements FilterResolver
{
    /**
     * @param iterable<FilterResolver> $chainFilterResolvers
     */
    public function __construct(
        private iterable $chainFilterResolvers,
    ) {}

    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): FilterSpecification
    {
        foreach ($this->chainFilterResolvers as $resolver) {
            try {
                return $resolver->getFilterFactory($dimension);
            } catch (DimensionNotSupportedByFilter $e) {
                // Continue to the next resolver.
            }
        }

        return new FilterSpecification(NullFilter::class);
    }
}
