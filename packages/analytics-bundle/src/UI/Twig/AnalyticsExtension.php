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

namespace Rekalogika\Analytics\Bundle\UI\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AnalyticsExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction(
                name: 'analytics_render_control',
                callable: [AnalyticsRuntime::class, 'renderControl'],
                options: [
                    'is_safe' => ['html'],
                ],
            ),
        ];
    }
}
