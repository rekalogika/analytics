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

use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Time\Filter\TimeBinFilterResolver;
use Rekalogika\Analytics\Time\Formatter\TimeBinHtmlifier;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    //
    // filter
    //

    if (interface_exists(FilterResolver::class)) {
        $services
            ->set('rekalogika.analytics.ux_panel.filter_resolver.time_bin')
            ->class(TimeBinFilterResolver::class)
            ->tag('rekalogika.analytics.ux-panel.filter_resolver', [
                'priority' => -50,
            ])
        ;
    }

    //
    // formatter
    //

    if (interface_exists(Htmlifier::class)) {
        $services
            ->set('rekalogika.analytics.time.formatter.time_bin_htmlifier')
            ->class(TimeBinHtmlifier::class)
            ->args([
                '$stringifier' => service(Stringifier::class),
            ])
            ->tag('rekalogika.analytics.htmlifier', [
                'priority' => 0,
            ])
        ;
    }
};
