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

use Rekalogika\Analytics\TimeInterval\Types\DateType;
use Rekalogika\Analytics\TimeInterval\Types\HourType;
use Rekalogika\Analytics\TimeInterval\Types\MonthType;
use Rekalogika\Analytics\TimeInterval\Types\QuarterType;
use Rekalogika\Analytics\TimeInterval\Types\TimeIntervalType;
use Rekalogika\Analytics\TimeInterval\Types\WeekDateType;
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

        foreach ($this->getTypes() as $id => $type) {
            $typeDefinition[$id] = [
                'class' => $type,
            ];
        }

        $container->setParameter(
            'doctrine.dbal.connection_factory.types',
            $typeDefinition,
        );
    }

    /**
     * @return \Traversable<string,class-string<TimeIntervalType>>
     */
    private function getTypes(): \Traversable
    {
        yield 'rekalogika_analytics_date' => DateType::class;
        yield 'rekalogika_analytics_hour' => HourType::class;
        yield 'rekalogika_analytics_month' => MonthType::class;
        yield 'rekalogika_analytics_quarter' => QuarterType::class;
        yield 'rekalogika_analytics_week_date' => WeekDateType::class;
        yield 'rekalogika_analytics_week' => WeekType::class;
        yield 'rekalogika_analytics_week_year' => WeekYearType::class;
        yield 'rekalogika_analytics_year' => YearType::class;
    }
}
