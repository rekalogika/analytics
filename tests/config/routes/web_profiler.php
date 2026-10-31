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

use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routingDir = (new WebProfilerBundle())->getPath() . '/Resources/config/routing';

    $extension = is_file($routingDir . '/wdt.php') ? 'php' : 'xml';

    $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.' . $extension)
        ->prefix('/_wdt');

    $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.' . $extension)
        ->prefix('/_profiler');
};
