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

use Doctrine\DBAL\Types\Type;
use Rekalogika\Analytics\Core\Exception\LogicException;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\HllType;
use Rekalogika\Analytics\Time\Doctrine\Types\DateType;
use Rekalogika\Analytics\Time\Doctrine\Types\HourType;
use Rekalogika\Analytics\Time\Doctrine\Types\MonthType;
use Rekalogika\Analytics\Time\Doctrine\Types\QuarterType;
use Rekalogika\Analytics\Time\Doctrine\Types\WeekDateType;
use Rekalogika\Analytics\Time\Doctrine\Types\WeekType;
use Rekalogika\Analytics\Time\Doctrine\Types\WeekYearType;
use Rekalogika\Analytics\Time\Doctrine\Types\YearType;
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
            throw new LogicException('The type definition is not an array.');
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
     * @return \Traversable<string,class-string<Type>>
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
        yield 'rekalogika_hll' => HllType::class;
    }
}
