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
use Psr\Log\LoggerInterface;
use Rekalogika\Analytics\Bundle\Command\RefreshSummaryCommand;
use Rekalogika\Analytics\Bundle\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Bundle\EventListener\RefreshLoggerEventSubscriber;
use Rekalogika\Analytics\Doctrine\Schema\SummaryPostGenerateSchemaTableListener;
use Rekalogika\Analytics\EventListener\NewEntitySignalListener;
use Rekalogika\Analytics\EventListener\SourceEntityListener;
use Rekalogika\Analytics\EventListener\SummaryEntityListener;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\SummaryManager\DefaultSummaryManagerRegistry;
use Rekalogika\Analytics\SummaryManager\NewEntitySignalConverter;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManagerRegistry;
use Rekalogika\Analytics\SummaryManager\SignalGenerator;
use Rekalogika\Analytics\SummaryManager\SummaryRefresherFactory;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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
            '$signalGenerator' => service('rekalogika.analytics.signal_generator'),
            '$eventDispatcher' => service('event_dispatcher')->nullOnInvalid(),
        ]);

    $services
        ->set('rekalogika.analytics.signal_generator')
        ->class(SignalGenerator::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$partitionManagerRegistry' => service('rekalogika.analytics.partition_manager_registry'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.new_entity_signal_converter')
        ->class(NewEntitySignalConverter::class)
        ->args([
            '$summaryRefresherFactory' => service('rekalogika.analytics.summary_refresher_factory'),
        ])
    ;

    // @todo tag as event listener
    $services
        ->set('rekalogika.analytics.new_entity_signal_listener')
        ->class(NewEntitySignalListener::class)
        ->args([
            '$newEntitySignalConverter' => service('rekalogika.analytics.new_entity_signal_converter'),
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
            '$signalGenerator' => service('rekalogika.analytics.signal_generator'),
            '$newEntitySignalListener' => service('rekalogika.analytics.new_entity_signal_listener'),
        ])
        ->tag('doctrine.event_listener', [
            'event' => Events::onFlush,
        ])
    ;

    $services
        ->set('rekalogika.analytics.event_subscriber.refresh_logger')
        ->class(RefreshLoggerEventSubscriber::class)
        ->args([
            '$logger' => service(LoggerInterface::class),
        ])
        ->tag('kernel.event_subscriber')
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
};
