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

namespace Rekalogika\Analytics\Symfony;

use Doctrine\ORM\Tools\ToolEvents;
use Psr\Log\LoggerInterface;
use Rekalogika\Analytics\Doctrine\Schema\SummaryPostGenerateSchemaTableListener;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\SummaryManager\DefaultSummaryManagerRegistry;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Rekalogika\Analytics\Symfony\Command\RefreshSummaryCommand;
use Rekalogika\Analytics\Symfony\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Symfony\EventListener\RefreshLoggerEventSubscriber;
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
        ->set(SummaryManagerRegistry::class)
        ->class(DefaultSummaryManagerRegistry::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$metadataFactory' => service(SummaryMetadataFactory::class),
            '$propertyAccessor' => service('property_accessor'),
            '$eventDispatcher' => service('event_dispatcher')->nullOnInvalid(),
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
        ->set('rekalogika.analytics.event_subscriber.refresh_logger')
        ->class(RefreshLoggerEventSubscriber::class)
        ->args([
            '$logger' => service(LoggerInterface::class),
            '$stopWatch' => service('debug.stopwatch')->nullOnInvalid(),
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
};
