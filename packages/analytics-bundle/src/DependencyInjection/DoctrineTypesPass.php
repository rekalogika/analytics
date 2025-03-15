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

use Rekalogika\Analytics\TimeInterval\Types\AbstractTimeDimensionType;
use Rekalogika\Analytics\TimeInterval\Types\DateType;
use Rekalogika\Analytics\TimeInterval\Types\DayOfMonthType;
use Rekalogika\Analytics\TimeInterval\Types\DayOfWeekType;
use Rekalogika\Analytics\TimeInterval\Types\DayOfYearType;
use Rekalogika\Analytics\TimeInterval\Types\HourOfDayType;
use Rekalogika\Analytics\TimeInterval\Types\HourType;
use Rekalogika\Analytics\TimeInterval\Types\MonthOfYearType;
use Rekalogika\Analytics\TimeInterval\Types\MonthType;
use Rekalogika\Analytics\TimeInterval\Types\QuarterOfYearType;
use Rekalogika\Analytics\TimeInterval\Types\QuarterType;
use Rekalogika\Analytics\TimeInterval\Types\WeekDateType;
use Rekalogika\Analytics\TimeInterval\Types\WeekOfMonthType;
use Rekalogika\Analytics\TimeInterval\Types\WeekOfYearType;
use Rekalogika\Analytics\TimeInterval\Types\WeekType;
use Rekalogika\Analytics\TimeInterval\Types\WeekYearType;
use Rekalogika\Analytics\TimeInterval\Types\YearType;
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
