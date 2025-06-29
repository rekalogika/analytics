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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\TimeBin;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<TimeBinFilter>
 */
final readonly class TimeBinFilterFactory implements FilterFactory
{
    #[\Override]
    public static function getFilterClass(): string
    {
        return TimeBinFilter::class;
    }

    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
    ): TimeBinFilter {
        $valueResolver = $dimension->getValueResolver();

        if (!$valueResolver instanceof TimeBinValueResolver) {
            throw new InvalidArgumentException(\sprintf(
                'NumberRangesFilter needs the value resolver of "%s", "%s" given',
                TimeBinValueResolver::class,
                get_debug_type($valueResolver),
            ));
        }

        $typeClass = $valueResolver->getTypeClass();

        return new TimeBinFilter(
            dimension: $dimension->getName(),
            label: $dimension->getLabel(),
            inputArray: $inputArray,
            typeClass: $typeClass,
        );
    }
}
