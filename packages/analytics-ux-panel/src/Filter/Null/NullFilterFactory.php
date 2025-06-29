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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\Null;

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<NullFilter>
 */
final readonly class NullFilterFactory implements FilterFactory
{
    #[\Override]
    public static function getFilterClass(): string
    {
        return NullFilter::class;
    }

    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
    ): Filter {
        return new NullFilter(
            dimension: $dimension->getName(),
            label: $dimension->getLabel(),
        );
    }
}
