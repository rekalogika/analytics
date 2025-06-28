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
use Rekalogika\Analytics\Bundle\Command\RefreshSummaryCommand;
use Rekalogika\Analytics\Bundle\Command\UuidConvertSummaryToSourceCommand;
use Rekalogika\Analytics\Bundle\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Bundle\EventListener\RefreshLoggerEventSubscriber;
use Rekalogika\Analytics\Bundle\RefreshWorker\RefreshMessageHandler;
use Rekalogika\Analytics\Bundle\RefreshWorker\SymfonyRefreshFrameworkAdapter;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\MessageBusInterface;

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
    // refresh worker
    //

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
};
