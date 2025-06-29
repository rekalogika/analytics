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
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<DateRangeFilter>
 */
final readonly class DateRangeFilterFactory implements FilterFactory
{
    #[\Override]
    public static function getFilterClass(): string
    {
        return DateRangeFilter::class;
    }

    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter {
        $label = $dimension->getLabel();
        $valueResolver = $dimension->getValueResolver();

        if (!$valueResolver instanceof TimeBinValueResolver) {
            throw new InvalidArgumentException(\sprintf(
                'NumberRangesFilter needs the value resolver of "%s", "%s" given',
                TimeBinValueResolver::class,
                get_debug_type($valueResolver),
            ));
        }

        $typeClass = $valueResolver->getTypeClass();

        if (!is_a($typeClass, Date::class, true)) {
            throw new InvalidArgumentException(\sprintf(
                'DateRangeFilter needs the type class of "%s", "%s" given',
                TimeBin::class,
                $typeClass,
            ));
        }

        return new DateRangeFilter(
            label: $label,
            dimension: $dimension->getName(),
            typeClass: $typeClass,
            inputArray: $inputArray,
        );
    }
}
