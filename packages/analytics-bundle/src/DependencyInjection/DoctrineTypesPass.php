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

use Rekalogika\Analytics\Doctrine\Types\TimeInterval\DateType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\HourType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\MonthType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\QuarterType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\TimeIntervalType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\WeekDateType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\WeekType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\WeekYearType;
use Rekalogika\Analytics\Doctrine\Types\TimeInterval\YearType;
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
