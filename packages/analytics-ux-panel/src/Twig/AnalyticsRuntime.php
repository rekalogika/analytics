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

namespace Rekalogika\Analytics\UX\PanelBundle\Twig;

use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQuery;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class AnalyticsRuntime implements RuntimeExtensionInterface
{
    public function __construct(private Environment $twig) {}

    public function renderControl(
        PivotAwareQuery $query,
        ?string $urlParameter = null,
        ?string $target = null,
        ?string $output = null,
    ): string {
        return $this->twig
            ->load('@RekalogikaAnalyticsUXPanel/pivot_table_control.html.twig')
            ->renderBlock('control', [
                'query' => $query,
                'urlParameter' => $urlParameter,
                'target' => $target,
                'output' => $output,
                'panelId' => self::getPanelId($urlParameter, $target),
            ]);
    }

    /**
     * Returns an id that is unique for each panel on a page, and stable across
     * renders, so that Turbo can match the frames in the response with the
     * ones on the page.
     */
    private static function getPanelId(
        ?string $urlParameter,
        ?string $target,
    ): string {
        $key = $urlParameter ?? $target ?? '';

        // escape everything except alphanumerics and dashes, including the
        // underscore used as the escape character, so the mapping is injective
        $key = preg_replace_callback(
            '/[^A-Za-z0-9-]/',
            static fn(array $matches): string => '_' . bin2hex($matches[0]),
            $key,
        ) ?? '';

        return 'analytics-panel-' . $key;
    }
}
