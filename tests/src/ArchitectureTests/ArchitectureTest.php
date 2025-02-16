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

use Doctrine\Persistence\ManagerRegistry;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Psr\Container\ContainerInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;

final class ArchitectureTest
{
    /**
     * analytics-core deps
     */
    public function testPackageAnalyticsCore(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsCore())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsCore(),
                // dependencies
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Psr\EventDispatcher'),
                Selector::inNamespace('Rekalogika\DoctrineAdvancedGroupBy'),
                Selector::inNamespace('Symfony\Component\PropertyAccess'),
                Selector::inNamespace('Symfony\Component\Uid'),
                Selector::inNamespace('Symfony\Contracts\Service'),
                Selector::inNamespace('Symfony\Contracts\Translation'),

                // datetime
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\DateTimeZone::class),
                Selector::classname(\DateInterval::class),

                // collections
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\Countable::class),
                Selector::classname(\ArrayIterator::class),

                // misc
                Selector::classname(\Stringable::class),
                Selector::classname(\BackedEnum::class),
                Selector::classname(\UnitEnum::class),
                Selector::classname(\Override::class),
                Selector::classname(\Attribute::class),
                Selector::classname(\IntlDateFormatter::class),

                // reflections
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionAttribute::class),
                Selector::classname(\ReflectionObject::class),
                Selector::classname(\ReflectionProperty::class),
                Selector::classname(\ReflectionException::class),

                // exceptions
                Selector::classname(\RuntimeException::class),
                Selector::classname(\LogicException::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\UnexpectedValueException::class),
                Selector::classname(\Error::class),
            );
    }

    /**
     * analytics-core should not depend on bundle package
     */
    public function testPackageAnalyticsCoreNegative(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsCore())
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Analytics\Bundle'),
            );
    }

    /**
     * analytics-bundle deps
     */
    public function testPackageAnalyticsBundle(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Analytics\Bundle'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Doctrine\Bundle\DoctrineBundle'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Psr\Cache'),
                Selector::inNamespace('Psr\Log'),
                Selector::inNamespace('Psr\SimpleCache'),
                Selector::inNamespace('Rekalogika\Analytics\Bundle'),
                Selectors::selectAnalyticsCore(),
                Selector::inNamespace('Symfony\Component\Cache'),
                Selector::inNamespace('Symfony\Component\Console'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::inNamespace('Symfony\Component\EventDispatcher'),
                Selector::inNamespace('Symfony\Component\Form'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),
                Selector::inNamespace('Symfony\Component\Lock'),
                Selector::inNamespace('Symfony\Component\Messenger'),
                Selector::inNamespace('Symfony\Component\OptionsResolver'),
                Selector::inNamespace('Symfony\Component\Translation'),
                Selector::inNamespace('Symfony\Contracts\Service'),
                Selector::inNamespace('Symfony\Contracts\Translation'),
                Selector::classname(ManagerRegistry::class),
                Selector::classname(\Override::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\LogicException::class),
                Selector::classname(\RuntimeException::class),
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\Stringable::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayAccess::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\Traversable::class),
                Selector::classname(AssetMapperInterface::class),
                Selector::classname(ContainerInterface::class),
            );
    }
}
