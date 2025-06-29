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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<NumberRangesFilter<object>,NumberRangesFilterOptions<object>>
 */
final readonly class NumberRangesFilterFactory implements FilterFactory
{
    #[\Override]
    public static function getFilterClass(): string
    {
        return NumberRangesFilter::class;
    }

    #[\Override]
    public static function getOptionObjectClass(): string
    {
        return NumberRangesFilterOptions::class;
    }

    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
        ?object $options = null,
    ): NumberRangesFilter {
        if (!$options instanceof NumberRangesFilterOptions) {
            throw new InvalidArgumentException(\sprintf(
                'NumberRangesFilter needs the options of "%s", "%s" given',
                NumberRangesFilterOptions::class,
                get_debug_type($options),
            ));
        }

        return new NumberRangesFilter(
            options: $options,
            dimension: $dimension->getName(),
            inputArray: $inputArray,
        );
    }
}
