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

use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ToolEvents;
use Rekalogika\Analytics\Bundle\Chart\AnalyticsChartBuilder;
use Rekalogika\Analytics\Bundle\Chart\Implementation\ChartConfiguration;
use Rekalogika\Analytics\Bundle\Chart\Implementation\DefaultAnalyticsChartBuilder;
use Rekalogika\Analytics\Bundle\Command\DebugSummaryCommand;
use Rekalogika\Analytics\Bundle\Command\RefreshSummaryCommand;
use Rekalogika\Analytics\Bundle\Command\UuidConvertSummaryToSourceCommand;
use Rekalogika\Analytics\Bundle\DistinctValuesResolver\ChainDistinctValuesResolver;
use Rekalogika\Analytics\Bundle\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Bundle\EventListener\RefreshLoggerEventSubscriber;
use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainCellifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainHtmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainNumberifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\ChainStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\DefaultBackendCellifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\DefaultBackendNumberifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\DefaultBackendStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Implementation\TranslatableStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Numberifier;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\Formatter\Twig\CellifierRuntime;
use Rekalogika\Analytics\Bundle\Formatter\Twig\FormatterExtension;
use Rekalogika\Analytics\Bundle\Formatter\Twig\HtmlifierRuntime;
use Rekalogika\Analytics\Bundle\RefreshWorker\RefreshMessageHandler;
use Rekalogika\Analytics\Bundle\RefreshWorker\SymfonyRefreshFrameworkAdapter;
use Rekalogika\Analytics\Bundle\UI\FilterFactory;
use Rekalogika\Analytics\Bundle\UI\Implementation\DefaultFilterFactory;
use Rekalogika\Analytics\Bundle\UI\PivotAwareQueryFactory;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Formatter\NodeWrapperCellifier;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Formatter\NodeWrapperHtmlifier;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Formatter\NodeWrapperNumberifier;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Formatter\NodeWrapperStringifier;
use Rekalogika\Analytics\Bundle\UI\PivotTableRenderer;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory\DateRangeFilterFactory;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory\EqualFilterFactory;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory\NullFilterFactory;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory\NumberRangesFilterFactory;
use Rekalogika\Analytics\Bundle\UI\SpreadsheetRenderer;
use Rekalogika\Analytics\Bundle\UI\Twig\AnalyticsExtension;
use Rekalogika\Analytics\Bundle\UI\Twig\AnalyticsRuntime;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\DistinctValuesResolver\DoctrineDistinctValuesResolver;
use Rekalogika\Analytics\Engine\Doctrine\Schema\SummaryPostGenerateSchemaTableListener;
use Rekalogika\Analytics\Engine\EventListener\NewDirtyFlagListener;
use Rekalogika\Analytics\Engine\EventListener\SourceEntityListener;
use Rekalogika\Analytics\Engine\EventListener\SummaryEntityListener;
use Rekalogika\Analytics\Engine\RefreshWorker\RefreshScheduler;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\DirtyFlagGenerator;
use Rekalogika\Analytics\Engine\SummaryManager\PartitionManager\PartitionManagerRegistry;
use Rekalogika\Analytics\Engine\SummaryManager\RefreshWorker\DefaultRefreshClassPropertiesResolver;
use Rekalogika\Analytics\Engine\SummaryManager\RefreshWorker\DefaultRefreshRunner;
use Rekalogika\Analytics\Engine\SummaryManager\SummaryRefresherFactory;
use Rekalogika\Analytics\Metadata\Implementation\CachingAttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultAttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultDimensionGroupMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultDimensionMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set('rekalogika.analytics.query_result_limit', 5000);
    $parameters->set('rekalogika.analytics.filling_nodes_limit', 10000);

    $services = $containerConfigurator->services();

    //
    // attribute collection factory
    //

    $services->alias(
        'rekalogika.analytics.attribute_collection_factory',
        'rekalogika.analytics.metadata.attribute_collection_factory',
    );

    $services
        ->set('rekalogika.analytics.metadata.attribute_collection_factory')
        ->class(DefaultAttributeCollectionFactory::class);

    $services
        ->set('rekalogika.analytics.metadata.attribute_collection_factory.caching')
        ->class(CachingAttributeCollectionFactory::class)
        ->decorate('rekalogika.analytics.metadata.attribute_collection_factory')
        ->args([
            '$decorated' => service('.inner'),
        ]);

    //
    // dimension metadata factory
    //

    $services
        ->set('rekalogika.analytics.dimension_metadata_factory')
        ->class(DefaultDimensionMetadataFactory::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$attributeCollectionFactory' => service('rekalogika.analytics.metadata.attribute_collection_factory'),
            '$dimensionGroupMetadataFactory' => service('rekalogika.analytics.dimension_class_metadata_factory'),
        ])
    ;

    //
    // dimension class metadata factory
    //

    $services
        ->set('rekalogika.analytics.dimension_class_metadata_factory')
        ->class(DefaultDimensionGroupMetadataFactory::class)
        ->args([
            '$attributeCollectionFactory' => service('rekalogika.analytics.metadata.attribute_collection_factory'),
        ])
    ;

    //
    // summary metadata factory
    //

    $services->alias(
        SummaryMetadataFactory::class,
        'rekalogika.analytics.summary_metadata_factory',
    );

    $services
        ->set('rekalogika.analytics.summary_metadata_factory')
        ->class(DefaultSummaryMetadataFactory::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$attributeCollectionFactory' => service('rekalogika.analytics.metadata.attribute_collection_factory'),
            '$dimensionMetadataFactory' => service('rekalogika.analytics.dimension_metadata_factory'),
        ])
    ;

    //
    // source metadata factory
    //

    $services->alias(
        SourceMetadataFactory::class,
        'rekalogika.analytics.source_metadata_factory',
    );

    $services
        ->set('rekalogika.analytics.source_metadata_factory')
        ->class(DefaultSourceMetadataFactory::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ])
    ;

    //
    // partition
    //

    $services
        ->set('rekalogika.analytics.partition_manager_registry')
        ->class(PartitionManagerRegistry::class)
        ->args([
            '$metadataFactory' => service(SummaryMetadataFactory::class),
            '$propertyAccessor' => service('property_accessor'),
        ])
    ;

    //
    // summary manager
    //

    $services->alias(
        SummaryManager::class,
        'rekalogika.analytics.summary_manager',
    );

    $services
        ->set('rekalogika.analytics.summary_manager')
        ->class(DefaultSummaryManager::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$metadataFactory' => service(SummaryMetadataFactory::class),
            '$propertyAccessor' => service('property_accessor'),
            '$refresherFactory' => service('rekalogika.analytics.summary_refresher_factory'),
            '$queryResultLimit' => '%rekalogika.analytics.query_result_limit%',
            '$fillingNodesLimit' => '%rekalogika.analytics.filling_nodes_limit%',
        ])
    ;

    $services
        ->set('rekalogika.analytics.summary_refresher_factory')
        ->class(SummaryRefresherFactory::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$metadataFactory' => service(SummaryMetadataFactory::class),
            '$partitionManagerRegistry' => service('rekalogika.analytics.partition_manager_registry'),
            '$dirtyFlagGenerator' => service('rekalogika.analytics.dirty_flag_generator'),
            '$eventDispatcher' => service('event_dispatcher')->nullOnInvalid(),
        ]);

    $services
        ->set('rekalogika.analytics.dirty_flag_generator')
        ->class(DirtyFlagGenerator::class)
        ->args([
            '$sourceMetadataFactory' => service(SourceMetadataFactory::class),
            '$partitionManagerRegistry' => service('rekalogika.analytics.partition_manager_registry'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.doctrine.schema.post_generate')
        ->class(SummaryPostGenerateSchemaTableListener::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$managerRegistry' => service('doctrine'),
        ])
        ->tag('doctrine.event_listener', [
            'event' => ToolEvents::postGenerateSchemaTable,
        ])
    ;

    $services
        ->set('rekalogika.analytics.doctrine.source_entity_listener')
        ->class(SourceEntityListener::class)
        ->args([
            '$dirtyFlagGenerator' => service('rekalogika.analytics.dirty_flag_generator'),
            '$eventDispatcher' => service('event_dispatcher')->nullOnInvalid(),
        ])
        ->tag('doctrine.event_listener', [
            'event' => Events::onFlush,
        ])
        ->tag('doctrine.event_listener', [
            'event' => Events::postFlush,
        ])
        ->tag('kernel.reset', [
            'method' => 'reset',
        ])
    ;

    $services
        ->set('rekalogika.analytics.event_subscriber.refresh_logger')
        ->class(RefreshLoggerEventSubscriber::class)
        ->args([
            '$logger' => service('logger')->ignoreOnInvalid(),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('monolog.logger', [
            'channel' => 'rekalogika.analytics',
        ])
    ;

    $services
        ->set('rekalogika.analytics.event_subscriber.refresh_command_output')
        ->class(RefreshCommandOutputEventSubscriber::class)
        ->tag('kernel.event_subscriber')
        ->tag('kernel.reset', [
            'method' => 'reset',
        ])
    ;

    $services
        ->set('rekalogika.analytics.summary_entity_listener')
        ->class(SummaryEntityListener::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'prePersist',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'preUpdate',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'preRemove',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'postLoad',
        ])
    ;

    //
    // CLI
    //


    $services
        ->set('rekalogika.analytics.command.refresh_summary')
        ->class(RefreshSummaryCommand::class)
        ->args([
            '$summaryManager' => service(SummaryManager::class),
            '$refreshCommandOutputEventSubscriber' => service('rekalogika.analytics.event_subscriber.refresh_command_output'),
        ])
        ->tag('console.command')
    ;

    $services
        ->set('rekalogika.analytics.command.uuid_convert_summary_to_source')
        ->class(UuidConvertSummaryToSourceCommand::class)
        ->tag('console.command')
    ;

    $services
        ->set('rekalogika.analytics.command.debug_summary')
        ->class(DebugSummaryCommand::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$translator' => service('translator'),
        ])
        ->tag('console.command')
    ;

    //
    // refresh worker framework
    //

    $services
        ->set('rekalogika.analytics.new_dirty_flag_listener')
        ->class(NewDirtyFlagListener::class)
        ->args([
            '$partitionManagerRegistry' => service('rekalogika.analytics.partition_manager_registry'),
            '$refreshScheduler' => service('rekalogika.analytics.refresh_worker.refresh_scheduler'),
        ])
        ->tag('kernel.event_listener', [
            'method' => 'onNewDirtyFlag',
        ])
    ;

    $services
        ->set('rekalogika.analytics.refresh_worker.refresh_scheduler')
        ->class(RefreshScheduler::class)
        ->args([
            '$adapter' => service('rekalogika.analytics.refresh_worker.refresh_framework_adapter')->nullOnInvalid(),
            '$runner' => service('rekalogika.analytics.refresh_worker.default_refresh_runner'),
            '$propertiesResolver' => service('rekalogika.analytics.refresh_worker.class_properties_resolver'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.refresh_worker.class_properties_resolver')
        ->class(DefaultRefreshClassPropertiesResolver::class)
    ;

    $services
        ->set('rekalogika.analytics.refresh_worker.default_refresh_runner')
        ->class(DefaultRefreshRunner::class)
        ->args([
            '$summaryRefresherFactory' => service('rekalogika.analytics.summary_refresher_factory'),
            '$eventDispatcher' => service('event_dispatcher')->nullOnInvalid(),
        ])
    ;

    $services
        ->set('rekalogika.analytics.refresh_worker.refresh_framework_adapter')
        ->class(SymfonyRefreshFrameworkAdapter::class)
        ->args([
            '$lockFactory' => service('lock.factory'),
            '$cache' => service('cache.app'),
            '$messageBus' => service(MessageBusInterface::class),
            '$logger' => service('logger')->ignoreOnInvalid(),
        ])
        ->tag('monolog.logger', [
            'channel' => 'rekalogika.analytics',
        ])
    ;

    $services
        ->set('rekalogika.analytics.refresh_worker.refresh_message_handler')
        ->class(RefreshMessageHandler::class)
        ->args([
            '$refreshScheduler' => service('rekalogika.analytics.refresh_worker.refresh_scheduler'),
            '$logger' => service('logger')->ignoreOnInvalid(),
        ])
        ->tag('messenger.message_handler')
        ->tag('monolog.logger', [
            'channel' => 'rekalogika.analytics',
        ])
    ;

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

    $services
        ->set('rekalogika.analytics.twig.runtime.htmlifier')
        ->class(HtmlifierRuntime::class)
        ->tag('twig.runtime')
        ->args([
            '$htmlifier' => service(Htmlifier::class),
        ])
    ;

    $services
        ->set('rekalogika.analytics.twig.runtime.cellifier')
        ->class(CellifierRuntime::class)
        ->tag('twig.runtime')
        ->args([
            '$cellifier' => service(Cellifier::class),
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
            '$twig' => service('twig'),
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
        ->set(DefaultBackendStringifier::class)
        ->tag('rekalogika.analytics.backend_stringifier', [
            'priority' => -1000,
        ])
    ;

    $services
        ->set(TranslatableStringifier::class)
        ->args([
            '$translator' => service('translator'),
        ])
        ->tag('rekalogika.analytics.backend_stringifier', [
            'priority' => -900,
        ])
    ;

    $services
        ->set(Stringifier::class)
        ->class(ChainStringifier::class)
        ->args([
            '$backendStringifiers' => tagged_iterator('rekalogika.analytics.backend_stringifier'),
        ])
    ;

    #
    # htmlifier
    #

    $services
        ->set(Htmlifier::class)
        ->class(ChainHtmlifier::class)
        ->args([
            '$backendHtmlifiers' => tagged_iterator('rekalogika.analytics.backend_htmlifier'),
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
            '$backendNumberifiers' => tagged_iterator('rekalogika.analytics.backend_numberifier'),
        ])
    ;

    $services
        ->set(DefaultBackendNumberifier::class)
        ->tag('rekalogika.analytics.backend_numberifier', [
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
            '$backendCellifiers' => tagged_iterator('rekalogika.analytics.backend_cellifier'),
            '$stringifier' => service(Stringifier::class),
        ])
    ;

    $services
        ->set(DefaultBackendCellifier::class)
        ->tag('rekalogika.analytics.backend_cellifier', [
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

    //
    // node wrapper
    //

    $services
        ->set(NodeWrapperHtmlifier::class)
        ->tag('rekalogika.analytics.backend_htmlifier', [
            'priority' => -100,
        ])
    ;

    $services
        ->set(NodeWrapperNumberifier::class)
        ->tag('rekalogika.analytics.backend_numberifier', [
            'priority' => -100,
        ])
    ;

    $services
        ->set(NodeWrapperStringifier::class)
        ->tag('rekalogika.analytics.backend_stringifier', [
            'priority' => -100,
        ])
    ;

    $services
        ->set(NodeWrapperCellifier::class)
        ->tag('rekalogika.analytics.backend_cellifier', [
            'priority' => -100,
        ])
    ;
};
