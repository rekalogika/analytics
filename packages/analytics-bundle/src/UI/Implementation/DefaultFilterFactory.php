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
use Psr\Container\ContainerInterface;
use Rekalogika\Analytics\Bundle\UI\Filter;
use Rekalogika\Analytics\Bundle\UI\Filter\DateRangeFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\EqualFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\NullFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\NumberRangesFilter;
use Rekalogika\Analytics\Bundle\UI\FilterFactory;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;
use Rekalogika\Analytics\Contracts\Summary\TimeInterval;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\Model\TimeInterval\DayOfMonth;
use Rekalogika\Analytics\Model\TimeInterval\DayOfYear;
use Rekalogika\Analytics\Model\TimeInterval\HourOfDay;
use Rekalogika\Analytics\Model\TimeInterval\Month;
use Rekalogika\Analytics\Model\TimeInterval\Quarter;
use Rekalogika\Analytics\Model\TimeInterval\Week;
use Rekalogika\Analytics\Model\TimeInterval\WeekDate;
use Rekalogika\Analytics\Model\TimeInterval\WeekOfMonth;
use Rekalogika\Analytics\Model\TimeInterval\WeekOfYear;
use Rekalogika\Analytics\Model\TimeInterval\WeekYear;
use Rekalogika\Analytics\Model\TimeInterval\Year;

final readonly class DefaultFilterFactory implements FilterFactory
{
    public function __construct(
        private ContainerInterface $specificFilterFactories,
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    #[\Override]
    public function createFilter(
        string $summaryClass,
        string $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $dimension = $metadata->getFullyQualifiedDimension($dimension);
        $typeClass = $dimension->getTypeClass();

        if (
            $typeClass === null
            || $this->isDoctrineRelation($summaryClass, $dimension->getFullName())
        ) {
            $filterFactory = $this->getSpecificFilterFactory(EqualFilter::class);
        } elseif (\in_array($typeClass, [
            Year::class,
            Quarter::class,
            Month::class,
            WeekDate::class,
            DayOfMonth::class,
            DayOfYear::class,
            WeekOfMonth::class,
            WeekOfYear::class,
            HourOfDay::class,
            WeekYear::class,
            Week::class,
        ], true)) {
            $filterFactory = $this->getSpecificFilterFactory(NumberRangesFilter::class);
        } elseif (enum_exists($typeClass)) {
            $filterFactory = $this->getSpecificFilterFactory(EqualFilter::class);
        } elseif (is_a($typeClass, TimeInterval::class, true)) {
            $filterFactory = $this->getSpecificFilterFactory(DateRangeFilter::class);
        } else {
            $filterFactory = $this->getSpecificFilterFactory(NullFilter::class);
        }

        return $filterFactory->createFilter(
            summaryClass: $summaryClass,
            dimension: $dimension->getFullName(),
            inputArray: $inputArray,
        );
    }

    /**
     * @template T of Filter
     * @param class-string<T> $class
     * @return SpecificFilterFactory<T>
     */
    private function getSpecificFilterFactory(string $class): SpecificFilterFactory
    {
        $filterFactory = $this->specificFilterFactories->get($class);

        if (!$filterFactory instanceof SpecificFilterFactory) {
            throw new \InvalidArgumentException(\sprintf(
                'Expected %s, got %s',
                SpecificFilterFactory::class,
                get_debug_type($filterFactory),
            ));
        }

        /** @var SpecificFilterFactory<T> $filterFactory */

        return $filterFactory;
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
}
