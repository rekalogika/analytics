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

use Rekalogika\Analytics\UX\PanelBundle\Twig\AnalyticsExtension;
use Rekalogika\Analytics\UX\PanelBundle\Twig\AnalyticsRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

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
};
