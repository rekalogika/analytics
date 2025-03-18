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

use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\UI\Filter;
use Rekalogika\Analytics\Bundle\UI\Filter\EqualFilter;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;
use Rekalogika\Analytics\DistinctValuesResolver;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

/**
 * @implements SpecificFilterFactory<EqualFilter>
 */
final readonly class EqualFilterFactory implements SpecificFilterFactory
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private DistinctValuesResolver $distinctValuesResolver,
        private Stringifier $stringifier,
    ) {}

    #[\Override]
    public static function getFilterClass(): string
    {
        return EqualFilter::class;
    }

    #[\Override]
    public function createFilter(
        string $summaryClass,
        string $dimension,
        array $inputArray,
    ): Filter {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $dimensionMetadata = $metadata->getFullyQualifiedDimension($dimension);
        $label = $dimensionMetadata->getLabel();

        return new EqualFilter(
            class: $summaryClass,
            label: $label,
            stringifier: $this->stringifier,
            distinctValuesResolver: $this->distinctValuesResolver,
            dimension: $dimension,
            inputArray: $inputArray,
        );
    }
}
