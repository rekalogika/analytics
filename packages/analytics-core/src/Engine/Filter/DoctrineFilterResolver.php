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

namespace Rekalogika\Analytics\Engine\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\DimensionNotSupportedByFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\DefaultChoiceFilterOptions;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterSpecification;

final readonly class DoctrineFilterResolver implements FilterResolver
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private DistinctValuesResolver $distinctValuesResolver,
    ) {}

    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): FilterSpecification
    {
        $summaryClass = $dimension->getSummaryMetadata()->getSummaryClass();
        $name = $dimension->getName();

        if (!$this->isDoctrineRelation($summaryClass, $name)) {
            throw new DimensionNotSupportedByFilter();
        }

        $choices = $this->distinctValuesResolver
            ->getDistinctValues($summaryClass, $name, 100);

        if ($choices === null) {
            throw new DimensionNotSupportedByFilter();
        }

        /**
         * @psalm-suppress InvalidArgument
         * @var array<string,mixed> $choices
         * */
        $choices = iterator_to_array($choices, true);

        $options = new DefaultChoiceFilterOptions($choices);

        return new FilterSpecification(ChoiceFilter::class, $options);

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
