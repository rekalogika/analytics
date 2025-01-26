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

use Rekalogika\Analytics\Tests\App\Misc\DebugToolbarReplacerSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services
        ->load('Rekalogika\\Analytics\\Tests\\App\\', '../src/App/')
        ->exclude('../src/App/{Entity,Exception}');

    $services->set(DebugToolbarReplacerSubscriber::class)
        ->args([service('kernel')])
        ->tag('kernel.event_subscriber');
};
