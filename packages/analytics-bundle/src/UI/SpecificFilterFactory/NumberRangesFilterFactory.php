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

namespace Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;

use Rekalogika\Analytics\Bundle\UI\Filter\NumberRangesFilter;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Rekalogika\Analytics\Time\TimeBin;

/**
 * @implements SpecificFilterFactory<NumberRangesFilter>
 */
final readonly class NumberRangesFilterFactory implements SpecificFilterFactory
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    #[\Override]
    public static function getFilterClass(): string
    {
        return NumberRangesFilter::class;
    }

    #[\Override]
    public function createFilter(
        string $summaryClass,
        string $dimension,
        array $inputArray,
        ?object $options = null,
    ): NumberRangesFilter {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $dimensionMetadata = $metadata->getDimensionOrDimensionProperty($dimension);
        $label = $dimensionMetadata->getLabel();
        $typeClass = $dimensionMetadata->getTypeClass();

        if (
            $typeClass === null || (
                !is_a($typeClass, TimeBin::class, true)
                && !is_a($typeClass, RecurringTimeBin::class, true)
            )
        ) {
            throw new \InvalidArgumentException(\sprintf(
                'NumberRangesFilter needs the type class of "%s" or "%s", "%s" given',
                TimeBin::class,
                RecurringTimeBin::class,
                get_debug_type($typeClass),
            ));
        }

        return new NumberRangesFilter(
            dimension: $dimension,
            label: $label,
            inputArray: $inputArray,
            typeClass: $typeClass,
        );
    }
}
