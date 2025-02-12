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
use Rekalogika\Analytics\Bundle\Command\RefreshSummaryCommand;
use Rekalogika\Analytics\Bundle\DistinctValuesResolver\ChainDistinctValuesResolver;
use Rekalogika\Analytics\Bundle\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Bundle\EventListener\RefreshLoggerEventSubscriber;
use Rekalogika\Analytics\Bundle\RefreshWorker\RefreshMessageHandler;
use Rekalogika\Analytics\Bundle\RefreshWorker\SymfonyRefreshFrameworkAdapter;
use Rekalogika\Analytics\DistinctValuesResolver;
use Rekalogika\Analytics\DistinctValuesResolver\DoctrineDistinctValuesResolver;
use Rekalogika\Analytics\Doctrine\Schema\SummaryPostGenerateSchemaTableListener;
use Rekalogika\Analytics\EventListener\NewDirtyFlagListener;
use Rekalogika\Analytics\EventListener\SourceEntityListener;
use Rekalogika\Analytics\EventListener\SummaryEntityListener;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\RefreshWorker\RefreshScheduler;
use Rekalogika\Analytics\SummaryManager\DefaultSummaryManagerRegistry;
use Rekalogika\Analytics\SummaryManager\DirtyFlagGenerator;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManagerRegistry;
use Rekalogika\Analytics\SummaryManager\RefreshWorker\DefaultRefreshClassPropertiesResolver;
use Rekalogika\Analytics\SummaryManager\RefreshWorker\DefaultRefreshRunner;
use Rekalogika\Analytics\SummaryManager\SummaryRefresherFactory;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\MessageBusInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->set(SummaryMetadataFactory::class)
        ->class(DefaultSummaryMetadataFactory::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.partition_manager_registry')
        ->class(PartitionManagerRegistry::class)
        ->args([
            '$metadataFactory' => service(SummaryMetadataFactory::class),
            '$propertyAccessor' => service('property_accessor'),
        ])
    ;

    $services
        ->set(SummaryManagerRegistry::class)
        ->class(DefaultSummaryManagerRegistry::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$metadataFactory' => service(SummaryMetadataFactory::class),
            '$propertyAccessor' => service('property_accessor'),
            '$refresherFactory' => service('rekalogika.analytics.summary_refresher_factory'),
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
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$partitionManagerRegistry' => service('rekalogika.analytics.partition_manager_registry'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.command.refresh_summary')
        ->class(RefreshSummaryCommand::class)
        ->args([
            '$summaryManagerRegistry' => service(SummaryManagerRegistry::class),
            '$refreshCommandOutputEventSubscriber' => service('rekalogika.analytics.event_subscriber.refresh_command_output'),
        ])
        ->tag('console.command')
    ;

    $services
        ->set('rekalogika.analytics.doctrine.schema.post_generate')
        ->class(SummaryPostGenerateSchemaTableListener::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
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
        ])
        ->tag('rekalogika.analytics.distinct_values_resolver');
};
