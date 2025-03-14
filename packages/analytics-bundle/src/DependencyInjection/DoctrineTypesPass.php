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

namespace Rekalogika\Analytics\Bundle\DependencyInjection;

use Rekalogika\Analytics\TimeDimensionHierarchy\Types\AbstractTimeDimensionType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DateType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DayOfMonthType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DayOfWeekType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DayOfYearType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\HourOfDayType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\HourType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\MonthOfYearType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\MonthType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\QuarterOfYearType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\QuarterType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekDateType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekOfMonthType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekOfYearType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekYearType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\YearType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class DoctrineTypesPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $typeDefinition = $container
            ->getParameter('doctrine.dbal.connection_factory.types');

        if (!\is_array($typeDefinition)) {
            throw new \RuntimeException('The type definition is not an array.');
        }

        foreach ($this->getTypes() as $type) {
            $typeDefinition[$type] = [
                'class' => $type,
            ];
        }

        $container->setParameter(
            'doctrine.dbal.connection_factory.types',
            $typeDefinition,
        );
    }

    /**
     * @return \Traversable<class-string<AbstractTimeDimensionType>>
     */
    private function getTypes(): \Traversable
    {
        yield DateType::class;
        yield DayOfMonthType::class;
        yield DayOfWeekType::class;
        yield DayOfYearType::class;
        yield HourOfDayType::class;
        yield HourType::class;
        yield MonthOfYearType::class;
        yield MonthType::class;
        yield QuarterOfYearType::class;
        yield QuarterType::class;
        yield WeekDateType::class;
        yield WeekOfMonthType::class;
        yield WeekOfYearType::class;
        yield WeekType::class;
        yield WeekYearType::class;
        yield YearType::class;
    }
}
