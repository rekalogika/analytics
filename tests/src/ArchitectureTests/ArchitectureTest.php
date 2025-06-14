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

namespace Rekalogika\Analytics\Tests\ArchitectureTests;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\Query\QueryException;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ArchitectureTest
{
    public function testPackageAnalyticsCore(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsCore())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsCore(),
                Selectors::selectAnalyticsCoreException(),
                Selectors::selectAnalyticsContracts(),

                // dependencies
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Symfony\Component\Uid'),
                Selector::inNamespace('Symfony\Contracts\Translation'),
                Selector::inNamespace('Rekalogika\PivotTable\Contracts'),

                // datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),

                // misc
                Selector::classname(\Stringable::class),
                Selector::classname(\Override::class),

                // reflections
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionAttribute::class),
                // Selector::classname(\ReflectionObject::class),
                Selector::classname(\ReflectionProperty::class),
                Selector::classname(\ReflectionException::class),

                // exceptions
                Selector::classname(\Error::class),
            );
    }

    public function testPackageAnalyticsCoreException(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsCoreException())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsCoreException(),
                Selector::classname(\RuntimeException::class),
                Selector::classname(\LogicException::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\BadMethodCallException::class),
                Selector::classname(\UnexpectedValueException::class),
                Selector::classname(\OverflowException::class),
                Selector::classname(\UnderflowException::class),
                Selector::classname(\DomainException::class),
                Selector::classname(QueryException::class),
                Selector::classname(ConversionException::class),
            );
    }

    public function testPackageAnalyticsEngine(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsEngine())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsEngine(),
                Selectors::selectAnalyticsCore(),
                Selectors::selectAnalyticsCoreException(),
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsMetadata(),

                // dependencies
                Selector::inNamespace('Rekalogika\Analytics\SimpleQueryBuilder'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Keyset'),
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine'),
                Selector::inNamespace('Rekalogika\DoctrineAdvancedGroupBy'),

                // psr/symfony contracts
                Selector::inNamespace('Psr\EventDispatcher'),
                Selector::inNamespace('Symfony\Contracts\Service'),
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // symfony
                Selector::inNamespace('Symfony\Component\PropertyAccess'),
                Selector::inNamespace('Symfony\Component\Translation'),

                // doctrine
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Doctrine\DBAL'),

                // datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateInterval::class),

                // misc
                Selector::classname(\Stringable::class),
                Selector::classname(\Override::class),
                Selector::classname(\BackedEnum::class),
                Selector::classname(\UnitEnum::class),

                // reflections
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionProperty::class),

                // exceptions
                Selector::classname(\Error::class),
                Selector::classname(\TypeError::class),
                Selector::classname(\InvalidArgumentException::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\Iterator::class),
                Selector::classname(\Countable::class),
            );
    }

    public function testPackageAnalyticsBundle(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Analytics\Bundle'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsBundle(),

                // dependencies
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCore(),
                Selectors::selectAnalyticsCoreException(),
                Selectors::selectAnalyticsMetadata(),
                Selectors::selectAnalyticsEngine(),
                Selector::inNamespace('OzdemirBurak\Iris\Color'),
                Selectors::selectAnalyticsPivotTable(),

                // optional dependencies
                Selectors::selectAnalyticsUuid(),
                Selectors::selectAnalyticsTime(),
                Selectors::selectAnalyticsPostgreSQLHll(),

                // psr/symfony contracts
                Selector::inNamespace('Psr\Cache'),
                Selector::inNamespace('Psr\Log'),
                Selector::inNamespace('Psr\Container'),
                Selector::inNamespace('Psr\SimpleCache'),
                Selector::inNamespace('Symfony\Contracts\Service'),
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // symfony
                Selector::inNamespace('Symfony\Component\AssetMapper'),
                Selector::inNamespace('Symfony\Component\Config'),
                Selector::inNamespace('Symfony\Component\Cache'),
                Selector::inNamespace('Symfony\Component\Console'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::inNamespace('Symfony\Component\EventDispatcher'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),
                Selector::inNamespace('Symfony\Component\Lock'),
                Selector::inNamespace('Symfony\Component\Messenger'),
                Selector::inNamespace('Symfony\Component\Translation'),
                Selector::inNamespace('Symfony\UX\Chartjs'),

                // doctrine
                Selector::inNamespace('Doctrine\Bundle\DoctrineBundle'),
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Doctrine\DBAL\Types'),

                // other third-party libraries
                Selector::inNamespace('Twig'),
                Selector::inNamespace('PhpOffice\PhpSpreadsheet'),

                // php misc
                Selector::classname(\Override::class),
                Selector::classname(\Stringable::class),
                Selector::classname(\UnitEnum::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\Traversable::class),

                // php datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),

                // php reflection
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionException::class),
            );
    }

    public function testPackageTime(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsTime(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsTime(),
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCoreException(),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // doctrine
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),

                // php misc
                Selector::classname(\Stringable::class),
                Selector::classname(\Override::class),

                // php enum
                Selector::classname(\BackedEnum::class),

                // php datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\DateTimeZone::class),
                Selector::classname(\DateInterval::class),
                Selector::classname(\IntlDateFormatter::class),

                // exceptions
                Selector::classname(\Error::class),
            );
    }

    public function testPackagePostgreSQLHll(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsPostgreSQLHll(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsPostgreSQLHll(),
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCoreException(),

                // doctrine
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),

                // php misc
                Selector::classname(\Override::class),

                // php enum
                Selector::classname(\BackedEnum::class),
            );
    }

    public function testPackageUuid(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsUuid(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsUuid(),
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCore(), // @todo maybe try removing this?
                Selectors::selectAnalyticsCoreException(),

                // symfony
                Selector::inNamespace('Symfony\Component\Uid'),

                // doctrine
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),

                // php misc
                Selector::classname(\Override::class),

                // php datetime
                Selector::classname(\DateTimeInterface::class),
            );
    }

    public function testPackagePivotTable(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsPivotTable(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsPivotTable(),

                // exceptions
                Selector::classname(\LogicException::class),
                Selector::classname(\InvalidArgumentException::class),

                // array
                Selector::classname(\Countable::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\ArrayIterator::class),

                // misc
                Selector::classname(\UnitEnum::class),
                Selector::classname(\Override::class),
            );
    }
}
