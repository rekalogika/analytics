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
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin as TimeBinValueResolver;

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

        $dimensionMetadata = $metadata->getDimension($dimension);
        $label = $dimensionMetadata->getLabel();
        $valueResolver = $dimensionMetadata->getValueResolver();

        if (!$valueResolver instanceof TimeBinValueResolver) {
            throw new InvalidArgumentException(\sprintf(
                'NumberRangesFilter needs the value resolver of "%s", "%s" given',
                TimeBinValueResolver::class,
                get_debug_type($valueResolver),
            ));
        }

        $typeClass = $valueResolver->getTypeClass();

        return new NumberRangesFilter(
            dimension: $dimension,
            label: $label,
            inputArray: $inputArray,
            typeClass: $typeClass,
        );
    }
}
