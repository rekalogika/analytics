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

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ArchitectureTest
{
    public function testPackageAnalyticsContracts(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsContracts())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsContracts(),

                // internal packages
                Selector::inNamespace('Rekalogika\Analytics\SimpleQueryBuilder'),
                Selector::inNamespace('Rekalogika\Analytics\Metadata'),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // rekalogika
                Selector::inNamespace('Rekalogika\DoctrineAdvancedGroupBy'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),

                // doctrine
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\Common\Collections'),

                // php misc
                Selector::classname(\Stringable::class),
                Selector::classname(\Override::class),

                // php exception
                Selector::classname(\Throwable::class),
                Selector::classname(\RuntimeException::class),
                Selector::classname(\LogicException::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\BadMethodCallException::class),
                Selector::classname(\UnexpectedValueException::class),
                Selector::classname(\OverflowException::class),
                Selector::classname(\UnderflowException::class),
                Selector::classname(\DomainException::class),

                // php reflection
                Selector::classname(\ReflectionClass::class),

                // php array
                Selector::classname(\Traversable::class),
                Selector::classname(\Countable::class),
            );
    }

    public function testPackageAnalyticsCore(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsCore())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsCore(),

                // internal packages
                Selectors::selectAnalyticsMetadata(),
                Selectors::selectAnalyticsContracts(),

                // rekalogika
                Selector::inNamespace('Rekalogika\DoctrineAdvancedGroupBy'),

                // doctrine
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Migrations'),
                Selector::inNamespace('Doctrine\Common\Collections'),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeZone::class),
                Selector::classname(\DateTimeImmutable::class),

                // reflections
                Selector::classname(\ReflectionNamedType::class),

                // misc
                Selector::classname(\Override::class),
                Selector::classname(\UnitEnum::class),

                // exceptions
                Selector::classname(\Error::class),
            );
    }

    public function testPackageAnalyticsEngine(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsEngine())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsEngine(),

                // internal packages
                Selectors::selectAnalyticsCore(),
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsMetadata(),

                // internal packages (optional)
                Selectors::selectAnalyticsUXPanel(),

                // rekalogika
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
                Selector::inNamespace('Symfony\Component\Lock'),
                Selector::inNamespace('Symfony\Component\Uid'),

                // doctrine
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Doctrine\DBAL'),

                // datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\DateInterval::class),

                // misc
                Selector::classname(\Stringable::class),
                Selector::classname(\Override::class),
                Selector::classname(\BackedEnum::class),
                Selector::classname(\UnitEnum::class),

                // reflections
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionProperty::class),
                Selector::classname(\ReflectionNamedType::class),

                // exceptions
                Selector::classname(\Error::class),
                Selector::classname(\Throwable::class),
                Selector::classname(\TypeError::class),
                Selector::classname(\InvalidArgumentException::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\Iterator::class),
                Selector::classname(\Countable::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\WeakMap::class),
            );
    }

    public function testPackageAnalyticsBundle(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsBundle(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsBundle(),

                // internal packages
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCore(),
                Selectors::selectAnalyticsMetadata(),
                Selectors::selectAnalyticsEngine(),
                Selectors::selectPivotTable(),

                // internal packages (optional)
                Selectors::selectAnalyticsUuid(),
                Selectors::selectAnalyticsTime(),
                Selectors::selectAnalyticsPostgreSQLHll(),
                Selectors::selectAnalyticsPostgreSQLExtra(),
                Selectors::selectAnalyticsFrontend(),
                Selectors::selectAnalyticsPivotTable(),

                // rekalogika
                Selector::inNamespace('Rekalogika\Analytics\SimpleQueryBuilder'),

                // psr/symfony contracts
                Selector::inNamespace('Psr\Cache'),
                Selector::inNamespace('Psr\Log'),
                Selector::inNamespace('Psr\Container'),
                Selector::inNamespace('Psr\SimpleCache'),
                Selector::inNamespace('Symfony\Contracts\Service'),
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // symfony
                Selector::inNamespace('Symfony\Component\Config'),
                Selector::inNamespace('Symfony\Component\Cache'),
                Selector::inNamespace('Symfony\Component\Console'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::inNamespace('Symfony\Component\EventDispatcher'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),
                Selector::inNamespace('Symfony\Component\Messenger'),
                Selector::inNamespace('Symfony\Component\Translation'),

                // doctrine
                Selector::inNamespace('Doctrine\Bundle\DoctrineBundle'),
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Doctrine\DBAL\Types'),

                // php misc
                Selector::classname(\Override::class),
                Selector::classname(\Stringable::class),
                Selector::classname(\UnitEnum::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\RecursiveTreeIterator::class),

                // php datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\DateInterval::class),

                // php reflection
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionException::class),
            );
    }

    public function testPackageAnalyticsFrontend(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsFrontend(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsFrontend(),

                // dependencies
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCore(),
                Selectors::selectAnalyticsMetadata(),
                Selectors::selectAnalyticsPivotTable(),
                Selectors::selectPivotTable(),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // symfony
                Selector::inNamespace('Symfony\Component\Translation'),
                Selector::inNamespace('Symfony\UX\Chartjs'),

                // doctrine
                Selector::inNamespace('Doctrine\Common\Collections'),

                // other third-party libraries
                Selector::inNamespace('Twig'),
                Selector::inNamespace('OzdemirBurak\Iris\Color'),
                Selector::inNamespace('PhpOffice\PhpSpreadsheet'),

                // php misc
                Selector::classname(\Override::class),
                Selector::classname(\UnitEnum::class),
                Selector::classname(\BackedEnum::class),
                Selector::classname(\Stringable::class),
                Selector::classname(\NumberFormatter::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),

                // exception
                Selector::classname(\Throwable::class),
            );
    }

    public function testPackageAnalyticsUXPanel(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsUXPanel(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsUXPanel(),

                // internal packages
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsBundle(),
                Selectors::selectAnalyticsFrontend(),
                Selectors::selectAnalyticsMetadata(),

                // psr/symfony contracts
                Selector::inNamespace('Psr\Container'),
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // symfony
                Selector::inNamespace('Symfony\Component\AssetMapper'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),

                // doctrine
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Doctrine\Common\Collections'),

                // other third-party libraries
                Selector::inNamespace('Twig'),

                // php misc
                Selector::classname(\Override::class),
                Selector::classname(\Stringable::class),
                Selector::classname(\UnitEnum::class),
                Selector::classname(\BackedEnum::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\Traversable::class),

                // php datetime
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\DateTimeInterface::class),
            );
    }

    public function testPackageMetadata(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsMetadata(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsMetadata(),

                // internal packages
                Selectors::selectAnalyticsContracts(),

                // rekalogika
                Selector::inNamespace('Rekalogika\DoctrineAdvancedGroupBy'),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Cache'),
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // doctrine
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Doctrine\Persistence'),

                // symfony components
                Selector::inNamespace('Symfony\Component\Cache'),

                // php misc
                Selector::classname(\Attribute::class),
                Selector::classname(\Override::class),
                Selector::classname(\Stringable::class),

                // php enum
                Selector::classname(\UnitEnum::class),

                // php datetime
                Selector::classname(\DateTimeZone::class),

                // reflections
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionAttribute::class),
                Selector::classname(\ReflectionProperty::class),
                Selector::classname(\ReflectionException::class),
                Selector::classname(\ReflectionNamedType::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\RecursiveIterator::class),

                // exceptions
                Selector::classname(\Error::class),
            );
    }

    public function testPackageSerialization(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsSerialization(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsSerialization(),

                // internal packages
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsMetadata(),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // doctrine
                Selector::inNamespace('Doctrine\Common\Collections'),

                // php misc
                Selector::classname(\Override::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\Traversable::class),
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

                // internal packages
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCore(),
                Selectors::selectAnalyticsMetadata(),

                // optional deps
                Selectors::selectAnalyticsUXPanel(),
                Selectors::selectAnalyticsFrontend(),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // doctrine
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Migrations'),

                // php misc
                Selector::classname(\Stringable::class),
                Selector::classname(\Override::class),
                Selector::classname(\Attribute::class),

                // php enum
                Selector::classname(\BackedEnum::class),

                // php datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\DateTimeZone::class),
                Selector::classname(\DateInterval::class),
                Selector::classname(\IntlDateFormatter::class),

                // php array
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),

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

                // internal packages
                Selectors::selectAnalyticsContracts(),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // doctrine
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),

                // php misc
                Selector::classname(\Override::class),
                Selector::classname(\Stringable::class),

                // php enum
                Selector::classname(\BackedEnum::class),
            );
    }

    public function testPackagePostgreSQLExtra(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectAnalyticsPostgreSQLExtra(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsPostgreSQLExtra(),

                // internal packages
                Selectors::selectAnalyticsContracts(),

                // doctrine
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Migrations'),

                // php misc
                Selector::classname(\Override::class),
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

                // internal packages
                Selectors::selectAnalyticsContracts(),
                Selectors::selectAnalyticsCore(),

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

                // internal packages
                Selectors::selectAnalyticsContracts(),

                // rekalogika dependencies
                Selectors::selectPivotTable(),

                // psr/symfony contracts
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // php misc
                Selector::classname(\Override::class),

                // php array
                Selector::classname(\Traversable::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\WeakMap::class),
            );
    }

    public function testPackageRekalogikaPivotTable(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selectors::selectPivotTable(),
            )
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectPivotTable(),

                // exceptions
                Selector::classname(\LogicException::class),
                Selector::classname(\RuntimeException::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\BadMethodCallException::class),

                // array
                Selector::classname(\Countable::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\WeakMap::class),

                // misc
                Selector::classname(\UnitEnum::class),
                Selector::classname(\BackedEnum::class),
                Selector::classname(\Stringable::class),
                Selector::classname(\Override::class),
            );
    }
}
