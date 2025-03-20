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

use Rekalogika\Analytics\Bundle\UI\Filter;
use Rekalogika\Analytics\Bundle\UI\Filter\DateRangeFilter;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\TimeInterval;

/**
 * @implements SpecificFilterFactory<DateRangeFilter>
 */
final readonly class DateRangeFilterFactory implements SpecificFilterFactory
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    #[\Override]
    public static function getFilterClass(): string
    {
        return DateRangeFilter::class;
    }

    #[\Override]
    public function createFilter(
        string $summaryClass,
        string $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $dimensionMetadata = $metadata->getFullyQualifiedDimension($dimension);
        $label = $dimensionMetadata->getLabel();
        $typeClass = $dimensionMetadata->getTypeClass();

        if ($typeClass === null || !is_a($typeClass, TimeInterval::class, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'DateRangeFilter needs a specific type class of %s, %s given',
                TimeInterval::class,
                get_debug_type($typeClass),
            ));
        }

        return new DateRangeFilter(
            label: $label,
            dimension: $dimension,
            typeClass: $typeClass,
            inputArray: $inputArray,
        );
    }
}
