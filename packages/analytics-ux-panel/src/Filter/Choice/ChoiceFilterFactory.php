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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\Choice;

use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<ChoiceFilter>
 */
final readonly class ChoiceFilterFactory implements FilterFactory
{
    public function __construct(
        private DistinctValuesResolver $distinctValuesResolver,
        private Stringifier $stringifier,
    ) {}

    #[\Override]
    public static function getFilterClass(): string
    {
        return ChoiceFilter::class;
    }

    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
    ): Filter {
        return new ChoiceFilter(
            class: $dimension->getSummaryMetadata()->getSummaryClass(),
            stringifier: $this->stringifier,
            distinctValuesResolver: $this->distinctValuesResolver,
            dimension: $dimension,
            inputArray: $inputArray,
        );
    }
}
