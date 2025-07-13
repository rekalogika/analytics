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

use Rekalogika\Analytics\Bundle\Command\DebugSummaryCommand;
use Rekalogika\Analytics\Bundle\Command\RefreshCommand;
use Rekalogika\Analytics\Bundle\Command\RefreshRangeCommand;
use Rekalogika\Analytics\Bundle\Command\UuidConvertSummaryToSourceCommand;
use Rekalogika\Analytics\Bundle\DistinctValuesResolver\ChainDistinctValuesResolver;
use Rekalogika\Analytics\Bundle\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Bundle\EventListener\RefreshLoggerEventSubscriber;
use Rekalogika\Analytics\Bundle\RefreshAgent\SymfonyRefreshAgentDispatcher;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\DistinctValuesResolver\DoctrineDistinctValuesResolver;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgent;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

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

    //
    // CLI
    //


    $services
        ->set('rekalogika.analytics.command.refresh_range')
        ->class(RefreshRangeCommand::class)
        ->args([
            '$summaryManager' => service(SummaryManager::class),
            '$refreshCommandOutputEventSubscriber' => service('rekalogika.analytics.event_subscriber.refresh_command_output'),
        ])
        ->tag('console.command')
    ;

    $services
        ->set('rekalogika.analytics.command.refresh')
        ->class(RefreshCommand::class)
        ->args([
            '$refreshCommandOutputEventSubscriber' => service('rekalogika.analytics.event_subscriber.refresh_command_output'),
            '$summaryRefresherFactory' => service('rekalogika.analytics.summary_refresher_factory'),
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
    // refresh agent
    //

    $services
        ->set('rekalogika.analytics.bundle.refresh.dispatcher')
        ->class(SymfonyRefreshAgentDispatcher::class)
        ->args([
            '$messageBus' => service('messenger.bus.default'),
        ])
    ;

    $services
        ->set('rekalogika.analytics.refresh.agent')
        ->class(RefreshAgent::class)
        ->args([
            '$summaryRefresherFactory' => service('rekalogika.analytics.summary_refresher_factory'),
            '$refreshAgentLock' => service('rekalogika.analytics.engine.refresh.lock'),
        ])
        ->tag('messenger.message_handler', [
            'method' => 'run',
        ])
    ;

};
