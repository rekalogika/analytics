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
use Rekalogika\Analytics\UX\PanelBundle\DimensionNotSupportedByFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilter;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterSpecification;

final readonly class DoctrineFilterResolver implements FilterResolver
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {}

    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): FilterSpecification
    {
        $summaryClass = $dimension->getSummaryMetadata()->getSummaryClass();
        $name = $dimension->getName();

        if ($this->isDoctrineRelation($summaryClass, $name)) {
            return new FilterSpecification(ChoiceFilter::class);
        }

        throw new DimensionNotSupportedByFilter();
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
