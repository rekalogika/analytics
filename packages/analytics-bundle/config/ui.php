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

use Rekalogika\Analytics\Bundle\DistinctValuesResolver\ChainDistinctValuesResolver;
use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainCellifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainHtmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainNumberifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\DefaultCellifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\DefaultNumberifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\DefaultStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\TranslatableStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Numberifier;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\Formatter\Twig\FormatterExtension;
use Rekalogika\Analytics\Bundle\Formatter\Twig\HtmlifierRuntime;
use Rekalogika\Analytics\Bundle\UI\Chart\AnalyticsChartBuilder;
use Rekalogika\Analytics\Bundle\UI\Chart\Implementation\ChartConfiguration;
use Rekalogika\Analytics\Bundle\UI\Chart\Implementation\DefaultAnalyticsChartBuilder;
use Rekalogika\Analytics\Bundle\UI\Html\PivotTableRenderer;
use Rekalogika\Analytics\Bundle\UI\Spreadsheet\SpreadsheetRenderer;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Engine\DistinctValuesResolver\DoctrineDistinctValuesResolver;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\Internal\DefaultFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Rekalogika\Analytics\UX\PanelBundle\SpecificFilterFactory\DateRangeFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\SpecificFilterFactory\EqualFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\SpecificFilterFactory\NullFilterFactory;
use Rekalogika\Analytics\UX\PanelBundle\SpecificFilterFactory\NumberRangesFilterFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    //
    // distinct values resolver
    //

    $services
        ->set(DistinctValuesResolver::class)
        ->class(ChainDistinctValuesResolver::class);

    $services
        ->set(DoctrineDistinctValuesResolver::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$propertyAccessor' => service('property_accessor'),
        ])
        ->tag('rekalogika.analytics.distinct_values_resolver');

    //
    // frontend
    //

    $services
        ->set('rekalogika.analytics.twig.runtime.htmlifier')
        ->class(HtmlifierRuntime::class)
        ->tag('twig.runtime')
        ->args([
            '$htmlifier' => service(Htmlifier::class),
        ])
    ;

    $services
        ->set('rekalogika.analytics.twig.extension.formatter')
        ->class(FormatterExtension::class)
        ->tag('twig.extension')
    ;

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
            '$filterFactory' => service(FilterFactory::class),
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ])
    ;

    $services->alias(
        PivotTableRenderer::class,
        'rekalogika.analytics.pivot_table_renderer',
    );

    $services
        ->set('rekalogika.analytics.pivot_table_renderer')
        ->class(PivotTableRenderer::class)
        ->args([
            '$twig' => service('twig'),
        ])
    ;

    $services->alias(
        SpreadsheetRenderer::class,
        'rekalogika.analytics.spreadsheet_renderer',
    );

    $services
        ->set('rekalogika.analytics.spreadsheet_renderer')
        ->class(SpreadsheetRenderer::class)
        ->args([
            '$cellifier' => service(Cellifier::class),
        ])
    ;

    //
    // chart
    //

    $services->alias(
        AnalyticsChartBuilder::class,
        'rekalogika.analytics.chart_builder',
    );

    $services
        ->set('rekalogika.analytics.chart_builder')
        ->class(DefaultAnalyticsChartBuilder::class)
        ->args([
            '$localeSwitcher' => service('translation.locale_switcher'),
            '$chartBuilder' => service(ChartBuilderInterface::class),
            '$stringifier' => service(Stringifier::class),
            '$configuration' => service(ChartConfiguration::class),
            '$numberifier' => service(Numberifier::class),
        ])
    ;

    $services
        ->set(ChartConfiguration::class);

    //
    // stringifier
    //

    $services
        ->set(DefaultStringifier::class)
        ->tag('rekalogika.analytics.stringifier', [
            'priority' => -1000,
        ])
    ;

    $services
        ->set(TranslatableStringifier::class)
        ->args([
            '$translator' => service('translator'),
        ])
        ->tag('rekalogika.analytics.stringifier', [
            'priority' => -900,
        ])
    ;

    $services
        ->set(Stringifier::class)
        ->class(ChainStringifier::class)
        ->args([
            '$stringifiers' => tagged_iterator('rekalogika.analytics.stringifier'),
        ])
    ;

    #
    # htmlifier
    #

    $services
        ->set(Htmlifier::class)
        ->class(ChainHtmlifier::class)
        ->args([
            '$htmlifiers' => tagged_iterator('rekalogika.analytics.htmlifier'),
            '$stringifier' => service(Stringifier::class),
        ])
    ;

    #
    # numberifier
    #

    $services
        ->set(Numberifier::class)
        ->class(ChainNumberifier::class)
        ->args([
            '$numberifiers' => tagged_iterator('rekalogika.analytics.numberifier'),
        ])
    ;

    $services
        ->set(DefaultNumberifier::class)
        ->tag('rekalogika.analytics.numberifier', [
            'priority' => -1000,
        ])
    ;

    //
    // cellifier
    //

    $services
        ->set(Cellifier::class)
        ->class(ChainCellifier::class)
        ->args([
            '$cellifiers' => tagged_iterator('rekalogika.analytics.cellifier'),
            '$stringifier' => service(Stringifier::class),
        ])
    ;

    $services
        ->set(DefaultCellifier::class)
        ->tag('rekalogika.analytics.cellifier', [
            'priority' => -1000,
        ])
    ;

    //
    // filter
    //

    $services
        ->set(FilterFactory::class)
        ->class(DefaultFilterFactory::class)
        ->args([
            '$specificFilterFactories' => tagged_locator('rekalogika.analytics.specific_filter_factory', defaultIndexMethod: 'getFilterClass'),
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$managerRegistry' => service('doctrine'),
        ])
    ;

    $services
        ->set(DateRangeFilterFactory::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ])
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(EqualFilterFactory::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$distinctValuesResolver' => service(DistinctValuesResolver::class),
            '$stringifier' => service(Stringifier::class),
        ])
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(NumberRangesFilterFactory::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ])
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;

    $services
        ->set(NullFilterFactory::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ])
        ->tag('rekalogika.analytics.specific_filter_factory')
    ;
};
