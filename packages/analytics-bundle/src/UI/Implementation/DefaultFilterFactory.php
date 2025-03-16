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

namespace Rekalogika\Analytics\Bundle\UI\Implementation;

use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\UI\Filter;
use Rekalogika\Analytics\Bundle\UI\Filter\DateRangeFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\EqualFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\NullFilter;
use Rekalogika\Analytics\Bundle\UI\FilterFactory;
use Rekalogika\Analytics\DistinctValuesResolver;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\TimeInterval;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultFilterFactory implements FilterFactory
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private DistinctValuesResolver $distinctValuesResolver,
        private Stringifier $stringifier,
        private ManagerRegistry $managerRegistry,
    ) {}

    #[\Override]
    public function createFilter(
        string $summaryClass,
        string $dimension,
        array $inputArray,
    ): Filter {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $dimension = $metadata->getFullyQualifiedDimension($dimension);
        $typeClass = $dimension->getTypeClass();
        $label = $dimension->getLabel();

        if (
            $typeClass === null
            || enum_exists($typeClass)
            || $this->isDoctrineRelation($summaryClass, $dimension->getFullName())
        ) {
            return $this->createEqualFilter(
                summaryClass: $summaryClass,
                dimension: $dimension->getFullName(),
                label: $label,
                input: $inputArray,
            );
        } elseif (is_a($typeClass, TimeInterval::class, true)) {
            return $this->createDateRangeFilter(
                label: $label,
                dimension: $dimension->getFullName(),
                typeClass: $typeClass,
                input: $inputArray,
            );
        }

        return $this->createNullFilter($dimension->getFullName(), $label);
    }

    /**
     * @param class-string $summaryClass
     * @param string $dimension
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

    /**
     * @param class-string $summaryClass
     * @param array<string,mixed> $input
     */
    private function createEqualFilter(
        string $summaryClass,
        string $dimension,
        string|TranslatableInterface $label,
        array $input,
    ): EqualFilter {
        return new EqualFilter(
            class: $summaryClass,
            label: $label,
            stringifier: $this->stringifier,
            distinctValuesResolver: $this->distinctValuesResolver,
            dimension: $dimension,
            inputArray: $input,
        );
    }

    /**
     * @param array<string,mixed> $input
     * @param class-string<TimeInterval> $typeClass
     */
    private function createDateRangeFilter(
        TranslatableInterface|string $label,
        string $dimension,
        array $input,
        string $typeClass,
    ): DateRangeFilter {
        return new DateRangeFilter(
            label: $label,
            dimension: $dimension,
            typeClass: $typeClass,
            inputArray: $input,
        );
    }

    private function createNullFilter(
        string $dimension,
        TranslatableInterface|string $label,
    ): NullFilter {
        return new NullFilter(
            dimension: $dimension,
            label: $label,
        );
    }
}
