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
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange\DateRangeFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Null\NullFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\Filter\TimeBin\TimeBinFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver\DefaultFilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\Internal\FilterFactoryLocator;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Rekalogika\Analytics\UX\PanelBundle\Twig\AnalyticsExtension;
use Rekalogika\Analytics\UX\PanelBundle\Twig\AnalyticsRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
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
    // filter
    //

    $services
        ->set(FilterResolver::class)
        ->class(DefaultFilterResolver::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
        ])
    ;

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
            '$distinctValuesResolver' => service(DistinctValuesResolver::class),
            '$stringifier' => service(Stringifier::class),
        ])
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(TimeBinFilterFactory::class)
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(NullFilterFactory::class)
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;
};
