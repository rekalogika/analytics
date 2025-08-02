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
use Rekalogika\Analytics\Engine\Doctrine\EventListener\SourceEntityListener;
use Rekalogika\Analytics\Engine\Doctrine\EventListener\SummaryEntityListener;
use Rekalogika\Analytics\Engine\Doctrine\EventListener\SummaryPostGenerateSchemaTableListener;
use Rekalogika\Analytics\Engine\EventListener\DirtySummaryEventListener;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgent;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentLock;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentRunner;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
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

    //
    // doctrine event listeners
    //

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
            '$handlerFactory' => service('rekalogika.analytics.summary_manager.handler_factory'),
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
    // event listeners
    //

    $services
        ->set('rekalogika.analytics.new_dirty_flag_listener')
        ->class(DirtySummaryEventListener::class)
        ->args([
            '$refreshAgentRunner' => service('rekalogika.analytics.engine.refresh.runner'),
        ])
        ->tag('kernel.event_listener', [
            'method' => 'onDirtySummaryEvent',
        ])
    ;

    //
    // refresh agent
    //

    $services
        ->set('rekalogika.analytics.engine.refresh.lock')
        ->class(RefreshAgentLock::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
        ])
        ->tag('kernel.reset', [
            'method' => 'reset',
        ])
    ;

    $services
        ->set('rekalogika.analytics.engine.refresh.runner')
        ->class(RefreshAgentRunner::class)
        ->args([
            '$refreshAgentDispatcher' => service('rekalogika.analytics.bundle.refresh.dispatcher'),
            '$refreshAgentLock' => service('rekalogika.analytics.engine.refresh.lock'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.engine.refresh.agent')
        ->class(RefreshAgent::class)
        ->args([
            '$summaryRefresherFactory' => service('rekalogika.analytics.summary_refresher_factory'),
            '$refreshAgentLock' => service('rekalogika.analytics.engine.refresh.lock'),
        ])
    ;
};
