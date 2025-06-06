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

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\Query\QueryException;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Psr\Container\ContainerInterface;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\PivotTable\PivotTableTransformer;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

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
                Selector::inNamespace('Rekalogika\PivotTable\Contracts'),

                // rekapager
                Selector::classname(PageableInterface::class),
                Selector::classname(PageInterface::class),
                Selector::classname(QueryBuilderAdapter::class),
                Selector::classname(KeysetPageable::class),

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
                Selector::classname(\Iterator::class),

                // misc
                Selector::classname(\Stringable::class),
                Selector::classname(\BackedEnum::class),
                Selector::classname(\UnitEnum::class),
                Selector::classname(\Override::class),
                Selector::classname(\Attribute::class),

                // reflections
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\ReflectionAttribute::class),
                Selector::classname(\ReflectionObject::class),
                Selector::classname(\ReflectionProperty::class),
                Selector::classname(\ReflectionException::class),

                // exceptions
                Selectors::selectAnalyticsCoreException(),
                Selector::classname(\TypeError::class),
                Selector::classname(\Error::class),
                Selector::classname(\Throwable::class),
                Selector::classname(\InvalidArgumentException::class),

                // intl
                Selector::classname(\IntlDateFormatter::class),
            );
    }

    public function testPackageAnalyticsCoreException(): Rule
    {
        return PHPat::rule()
            ->classes(Selectors::selectAnalyticsCoreException())
            ->canOnlyDependOn()
            ->classes(
                Selectors::selectAnalyticsCoreException(),
                Selectors::selectAnalyticsCore(),
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
                Selector::inNamespace('Symfony\Component\Config'),
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Psr\Cache'),
                Selector::inNamespace('Psr\Log'),
                Selector::inNamespace('Psr\SimpleCache'),
                Selector::inNamespace('Rekalogika\Analytics\Bundle'),
                Selector::inNamespace('Rekalogika\Analytics\Exception'),
                Selectors::selectAnalyticsCore(),
                Selector::inNamespace('Symfony\Component\Cache'),
                Selector::inNamespace('Symfony\Component\Console'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::inNamespace('Symfony\Component\EventDispatcher'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),
                Selector::inNamespace('Symfony\Component\Lock'),
                Selector::inNamespace('Symfony\Component\Messenger'),
                Selector::inNamespace('Symfony\Component\OptionsResolver'),
                Selector::inNamespace('Symfony\Component\Translation'),
                Selector::inNamespace('Symfony\Contracts\Service'),
                Selector::inNamespace('Symfony\Contracts\Translation'),
                Selector::inNamespace('Rekalogika\PivotTable\Contracts'),
                Selector::classname(\Override::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\Exception::class),
                Selector::classname(\WeakMap::class),
                Selector::classname(\LogicException::class),
                Selector::classname(\RuntimeException::class),
                Selector::classname(\ReflectionClass::class),
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\Stringable::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\ArrayAccess::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\UnexpectedValueException::class),
                Selector::classname(AssetMapperInterface::class),
                Selector::classname(ContainerInterface::class),
                Selector::classname(\UnitEnum::class),
                Selector::inNamespace('OzdemirBurak\Iris\Color'),

                // twig
                Selector::classname(Environment::class),
                Selector::classname(RuntimeExtensionInterface::class),
                Selector::classname(TwigFunction::class),
                Selector::classname(TwigFilter::class),
                Selector::classname(AbstractExtension::class),

                // doctrine
                Selector::classname(Criteria::class),
                Selector::classname(Expression::class),

                // Chartjs
                Selector::classname(ChartBuilderInterface::class),
                Selector::classname(Chart::class),

                // pivot table
                Selector::classname(PivotTableTransformer::class),

                // PhpSpreadsheet
                Selector::classname(DataType::class),
                Selector::classname(Html::class),
                Selector::classname(Spreadsheet::class),
            );
    }

    /**
     * pivot-table deps
     */
    public function testPackagePivotTable(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\PivotTable'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\PivotTable'),
                Selector::classname(\LogicException::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\Countable::class),
                Selector::classname(\Override::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\UnitEnum::class),
            );
    }
}
