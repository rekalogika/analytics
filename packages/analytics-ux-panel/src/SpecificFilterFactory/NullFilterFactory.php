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

namespace Rekalogika\Analytics\UX\PanelBundle\SpecificFilterFactory;

use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\NullFilter;
use Rekalogika\Analytics\UX\PanelBundle\SpecificFilterFactory;

/**
 * @implements SpecificFilterFactory<NullFilter>
 */
final readonly class NullFilterFactory implements SpecificFilterFactory
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    #[\Override]
    public static function getFilterClass(): string
    {
        return NullFilter::class;
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

        $dimensionMetadata = $metadata->getDimension($dimension);
        $label = $dimensionMetadata->getLabel();

        return new NullFilter(
            dimension: $dimension,
            label: $label,
        );
    }
}
