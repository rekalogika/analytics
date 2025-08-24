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

namespace Rekalogika\Analytics\UX\PanelBundle\FilterResolver;

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\DimensionNotSupportedByFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\DefaultChoiceFilterOptions;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterSpecification;

final readonly class EnumFilterResolver implements FilterResolver
{
    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): FilterSpecification
    {
        $typeClass = $dimension->getTypeClass();

        if ($typeClass === null || !enum_exists($typeClass)) {
            throw new DimensionNotSupportedByFilter();
        }

        $choices = [];

        if (is_a($typeClass, \BackedEnum::class, true)) {
            // For backed enums, we use the value as the choice.

            /** @psalm-suppress MixedMethodCall */
            /** @var \BackedEnum $case */
            foreach ($typeClass::cases() as $case) {
                /**
                 * @psalm-suppress MixedAssignment
                 * @psalm-suppress MixedPropertyFetch
                 * @psalm-suppress MixedArrayOffset
                 */
                $choices[$case->value] = $case;
            }
        } elseif (is_a($typeClass, \UnitEnum::class, true)) {
            // For unit enums, we use the name as the choice.

            /** @psalm-suppress MixedMethodCall */
            /** @var \UnitEnum $case */
            foreach ($typeClass::cases() as $case) {
                /**
                 * @psalm-suppress MixedAssignment
                 * @psalm-suppress MixedPropertyFetch
                 * @psalm-suppress MixedArrayOffset
                 */
                $choices[$case->name] = $case;
            }
        } else {
            throw new DimensionNotSupportedByFilter();
        }

        /** @var array<string,\UnitEnum> $choices */

        $options = new DefaultChoiceFilterOptions($choices);

        return new FilterSpecification(ChoiceFilter::class, $options);
    }
}
