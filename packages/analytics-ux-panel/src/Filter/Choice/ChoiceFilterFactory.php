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

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<ChoiceFilter,ChoiceFilterOptions>
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
    public static function getOptionObjectClass(): string
    {
        return ChoiceFilterOptions::class;
    }

    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter {
        if (!$options instanceof ChoiceFilterOptions) {
            throw new InvalidArgumentException(\sprintf(
                'ChoiceFilter needs the options of "%s", "%s" given',
                ChoiceFilterOptions::class,
                get_debug_type($options),
            ));
        }

        return new ChoiceFilter(
            options: $options,
            stringifier: $this->stringifier,
            dimension: $dimension,
            inputArray: $inputArray,
        );
    }
}
