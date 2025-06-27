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
            Selector::inNamespace('Rekalogika\Analytics\Core'),
            Selector::NOT(
                self::selectAnalyticsCommon(),
            ),
        );
    }

    public static function selectAnalyticsCommon(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Common');
    }

    public static function selectAnalyticsContracts(): SelectorInterface
    {
        return Selector::AnyOf(
            Selector::inNamespace('Rekalogika\Analytics\Contracts'),
        );
    }

    public static function selectAnalyticsMetadata(): SelectorInterface
    {
        return Selector::AnyOf(
            Selector::inNamespace('Rekalogika\Analytics\Metadata'),
        );
    }

    public static function selectAnalyticsBundle(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Bundle');
    }

    public static function selectAnalyticsEngine(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Engine');
    }

    public static function selectAnalyticsTime(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Time');
    }

    public static function selectAnalyticsUuid(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\Uuid');
    }

    public static function selectAnalyticsPivotTable(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\PivotTable');
    }

    public static function selectPivotTable(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\PivotTable');
    }

    public static function selectAnalyticsPostgreSQLHll(): SelectorInterface
    {
        return Selector::inNamespace('Rekalogika\Analytics\PostgreSQLHll');
    }
}
