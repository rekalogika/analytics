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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

/**
 * @implements FilterFactory<ChoiceFilter,ChoiceFilterOptions>
 */
final readonly class ChoiceFilterFactory implements FilterFactory
{
    public function __construct(
        private Stringifier $stringifier,
        private Htmlifier $htmlifier,
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

    /**
     * @param ChoiceFilterOptions<mixed>|null $options
     * @param array<string,mixed> $inputArray
     */
    #[\Override]
    public function createFilter(
        DimensionMetadata $dimension,
        array $inputArray,
        ?object $options = null,
    ): ChoiceFilter {
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
            htmlifier: $this->htmlifier,
            dimension: $dimension,
            inputArray: $inputArray,
        );
    }
}
