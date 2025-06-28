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

namespace Rekalogika\Analytics\Frontend\Formatter\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormatterExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                name: 'analytics_to_html',
                callable: [HtmlifierRuntime::class, 'toHtml'],
                options: [
                    'is_safe' => ['html'],
                ],
            ),
        ];
    }
}
