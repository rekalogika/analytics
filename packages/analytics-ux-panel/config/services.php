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

namespace Rekalogika\Analytics\Bundle;

use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Engine\Filter\DoctrineFilterResolver;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange\DateRangeFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Null\NullFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges\NumberRangesFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver\ChainFilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver\EnumFilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\Internal\FilterFactoryLocator;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Rekalogika\Analytics\UX\PanelBundle\Twig\AnalyticsExtension;
use Rekalogika\Analytics\UX\PanelBundle\Twig\AnalyticsRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    //
    // pivot table
    //

    $services->alias(
        PivotAwareQueryFactory::class,
        'rekalogika.analytics.pivot_aware_query_factory',
    );

    $services
        ->set('rekalogika.analytics.pivot_aware_query_factory')
        ->class(PivotAwareQueryFactory::class)
        ->args([
            '$filterFactory' => service(FilterResolver::class),
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$filterFactoryLocator' => service('rekalogika.analytics.filter_factory_locator'),
        ])
    ;

    //
    // twig
    //

    $services
        ->set('rekalogika.analytics.twig.runtime.analytics')
        ->class(AnalyticsRuntime::class)
        ->tag('twig.runtime')
        ->args([
            '$twig' => service('twig'),
        ]);

    $services
        ->set('rekalogika.analytics.twig.extension.analytics')
        ->class(AnalyticsExtension::class)
        ->tag('twig.extension');

    //
    // filter resolver
    //

    $services
        ->set('rekalogika.analytics.ux_panel.filter_resolver')
        ->class(ChainFilterResolver::class)
        ->args([
            '$chainFilterResolvers' => tagged_iterator('rekalogika.analytics.ux-panel.filter_resolver'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.ux_panel.filter_resolver.doctrine')
        ->class(DoctrineFilterResolver::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$distinctValuesResolver' => service(DistinctValuesResolver::class),
        ])
        ->tag('rekalogika.analytics.ux-panel.filter_resolver', [
            'priority' => -100,
        ])
    ;

    $services
        ->set('rekalogika.analytics.ux_panel.filter_resolver.enum')
        ->class(EnumFilterResolver::class)
        ->tag('rekalogika.analytics.ux-panel.filter_resolver', [
            'priority' => -200,
        ])
    ;

    //
    // filter
    //

    $services->alias(
        FilterResolver::class,
        'rekalogika.analytics.ux_panel.filter_resolver',
    );

    $services
        ->set('rekalogika.analytics.filter_factory_locator')
        ->class(FilterFactoryLocator::class)
        ->args([
            '$container' => tagged_locator('rekalogika.analytics.specific_filter_factory', defaultIndexMethod: 'getFilterClass'),
        ])
    ;

    $services
        ->set(DateRangeFilterFactory::class)
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(ChoiceFilterFactory::class)
        ->args([
            '$stringifier' => service(Stringifier::class),
            '$htmlifier' => service(Htmlifier::class),
        ])
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(NumberRangesFilterFactory::class)
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(NullFilterFactory::class)
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;
};
