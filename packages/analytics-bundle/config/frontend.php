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

use Rekalogika\Analytics\Frontend\Chart\AnalyticsChartBuilder;
use Rekalogika\Analytics\Frontend\Chart\Implementation\ChartConfiguration;
use Rekalogika\Analytics\Frontend\Chart\Implementation\DefaultAnalyticsChartBuilder;
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainCellifier;
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainHtmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainNumberifier;
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\DefaultCellifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\DefaultNumberifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\DefaultStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\NumberFormatStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\TranslatableStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\Property\PropertyCellifier;
use Rekalogika\Analytics\Frontend\Formatter\Property\PropertyHtmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Property\PropertyNumberifier;
use Rekalogika\Analytics\Frontend\Formatter\Property\PropertyStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Formatter\Twig\FormatterExtension;
use Rekalogika\Analytics\Frontend\Formatter\Twig\HtmlifierRuntime;
use Rekalogika\Analytics\Frontend\Formatter\Twig\StringifierRuntime;
use Rekalogika\Analytics\Frontend\Html\ExpressionHtmlRenderer;
use Rekalogika\Analytics\Frontend\Html\HtmlRenderer;
use Rekalogika\Analytics\Frontend\Spreadsheet\SpreadsheetRenderer;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    //
    // twig
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
        ->set('rekalogika.analytics.twig.runtime.stringifier')
        ->class(StringifierRuntime::class)
        ->tag('twig.runtime')
        ->args([
            '$stringifier' => service(Stringifier::class),
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
        HtmlRenderer::class,
        'rekalogika.analytics.pivot_table_renderer',
    );

    $services
        ->set('rekalogika.analytics.pivot_table_renderer')
        ->class(HtmlRenderer::class)
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

    $services
        ->set(ExpressionHtmlRenderer::class)
        ->args([
            '$htmlifier' => service(Htmlifier::class),
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ]);

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
        ->set(PropertyStringifier::class)
        ->tag('rekalogika.analytics.stringifier', [
            'priority' => -500,
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

    $services
        ->set(PropertyHtmlifier::class)
        ->tag('rekalogika.analytics.htmlifier', [
            'priority' => -500,
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

    $services
        ->set(PropertyNumberifier::class)
        ->tag('rekalogika.analytics.numberifier', [
            'priority' => -500,
        ])
    ;

    $services
        ->set(NumberFormatStringifier::class)
        ->args([
            '$translator' => service('translator'),
        ])
        ->tag('rekalogika.analytics.stringifier', [
            'priority' => -100,
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

    $services
        ->set(PropertyCellifier::class)
        ->tag('rekalogika.analytics.cellifier', [
            'priority' => -500,
        ])
    ;
};
