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

namespace Rekalogika\Analytics\Tests\ArchitectureTests;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class Selectors
{
    private function __construct() {}

    public static function selectAnalyticsCore(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::inNamespace('Rekalogika\Analytics'),
            Selector::NOT(
                self::selectAnalyticsBundle(),
            ),
            Selector::NOT(
                self::selectAnalyticsContracts(),
            ),
            Selector::NOT(
                self::selectAnalyticsTime(),
            ),
            Selector::NOT(
                Selector::inNamespace('Rekalogika\Analytics\Tests'),
            ),
            Selector::NOT(
                self::selectAnalyticsCoreException(),
            ),
        );
    }

    public static function selectAnalyticsContracts(): SelectorInterface
    {
        return Selector::AnyOf(
            Selector::inNamespace('Rekalogika\Analytics\Attribute'),
            Selector::inNamespace('Rekalogika\Analytics\Contracts'),
        );
    }

    public static function selectAnalyticsBundle(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Bundle');
    }

    public static function selectAnalyticsTime(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Time');
    }

    public static function selectAnalyticsCoreException(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Exception');
    }
}
