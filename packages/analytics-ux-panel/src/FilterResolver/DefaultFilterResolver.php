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

use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange\DateRangeFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Null\NullFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\TimeBin\TimeBinFilter;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;

final readonly class DefaultFilterResolver implements FilterResolver
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {}

    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): string
    {
        $summaryClass = $dimension->getSummaryMetadata()->getSummaryClass();
        $typeClass = $dimension->getTypeClass();
        $valueResolver = $dimension->getValueResolver();

        if (
            $this->isDoctrineRelation($summaryClass, $dimension->getName())
        ) {
            return ChoiceFilter::class;
        } elseif ($valueResolver instanceof TimeBinValueResolver) {
            $typeClass = $valueResolver->getTypeClass();

            if (is_a($typeClass, Date::class, true)) {
                return DateRangeFilter::class;
            } else {
                return TimeBinFilter::class;
            }
        } elseif ($typeClass !== null && enum_exists($typeClass)) {
            return ChoiceFilter::class;
        }

        return NullFilter::class;
    }

    /**
     * @param class-string $summaryClass
     */
    private function isDoctrineRelation(
        string $summaryClass,
        string $dimension,
    ): bool {
        $doctrineMetadata = $this->managerRegistry
            ->getManagerForClass($summaryClass)
            ?->getClassMetadata($summaryClass);

        if ($doctrineMetadata === null) {
            return false;
        }

        return $doctrineMetadata->hasAssociation($dimension);
    }
}
