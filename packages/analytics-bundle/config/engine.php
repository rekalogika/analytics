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
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Doctrine\Schema\SummaryPostGenerateSchemaTableListener;
use Rekalogika\Analytics\Engine\EventListener\NewDirtyFlagListener;
use Rekalogika\Analytics\Engine\EventListener\SourceEntityListener;
use Rekalogika\Analytics\Engine\EventListener\SummaryEntityListener;
use Rekalogika\Analytics\Engine\RefreshWorker\RefreshScheduler;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\DirtyFlag\DirtyFlagGenerator;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\SummaryManager\RefreshWorker\DefaultRefreshClassPropertiesResolver;
use Rekalogika\Analytics\Engine\SummaryManager\RefreshWorker\DefaultRefreshRunner;
use Rekalogika\Analytics\Engine\SummaryManager\SummaryRefresherFactory;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    //
    // parameters
    //

    $parameters = $containerConfigurator->parameters();
    $parameters->set('rekalogika.analytics.query_result_limit', 5000);
    $parameters->set('rekalogika.analytics.filling_nodes_limit', 10000);

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
            '$handlerFactory' => service('rekalogika.analytics.summary_manager.handler_factory'),
            '$managerRegistry' => service('doctrine'),
            '$metadataFactory' => service(SummaryMetadataFactory::class),
            '$dirtyFlagGenerator' => service('rekalogika.analytics.dirty_flag_generator'),
            '$eventDispatcher' => service('event_dispatcher')->nullOnInvalid(),
        ]);

    $services
        ->set('rekalogika.analytics.dirty_flag_generator')
        ->class(DirtyFlagGenerator::class)
        ->args([
            '$sourceMetadataFactory' => service(SourceMetadataFactory::class),
            '$handlerFactory' => service('rekalogika.analytics.summary_manager.handler_factory'),
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
            '$handlerFactory' => service('rekalogika.analytics.summary_manager.handler_factory'),
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
        ->set('rekalogika.analytics.summary_manager.handler_factory')
        ->class(HandlerFactory::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
            '$sourceMetadataFactory' => service(SourceMetadataFactory::class),
            '$managerRegistry' => service('doctrine'),
            '$propertyAccessor' => service('property_accessor'),
        ])
        ->tag('kernel.reset', [
            'method' => 'reset',
        ])
    ;
};
