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

use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\EqualFilter;
use Rekalogika\Analytics\UX\PanelBundle\SpecificFilterFactory;

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
        ?object $options = null,
    ): Filter {
        return new EqualFilter(
            class: $summaryClass,
            stringifier: $this->stringifier,
            distinctValuesResolver: $this->distinctValuesResolver,
            summaryMetadataFactory: $this->summaryMetadataFactory,
            dimension: $dimension,
            inputArray: $inputArray,
        );
    }
}
