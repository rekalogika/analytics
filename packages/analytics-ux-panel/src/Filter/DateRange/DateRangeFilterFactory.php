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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<DateRangeFilter<object>,DateRangeFilterOptions<object>>
 */
final readonly class DateRangeFilterFactory implements FilterFactory
{
    #[\Override]
    public static function getFilterClass(): string
    {
        return DateRangeFilter::class;
    }

    #[\Override]
    public static function getOptionObjectClass(): string
    {
        return DateRangeFilterOptions::class;
    }

    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter {
        if (!$options instanceof DateRangeFilterOptions) {
            throw new InvalidArgumentException(\sprintf(
                'DateRangeFilter needs the options of "%s", "%s" given',
                DateRangeFilterOptions::class,
                get_debug_type($options),
            ));
        }

        return new DateRangeFilter(
            options: $options,
            dimension: $dimension->getName(),
            inputArray: $inputArray,
        );
    }
}
