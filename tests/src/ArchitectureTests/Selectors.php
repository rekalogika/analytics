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

use PHPat\Selector\Modifier\AllOfSelectorModifier;
use PHPat\Selector\Selector;

final readonly class Selectors
{
    private function __construct() {}

    public static function selectAnalyticsCore(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('Rekalogika\Analytics'),
            Selector::NOT(
                Selector::inNamespace('Rekalogika\Analytics\Bundle'),
            ),
            Selector::NOT(
                Selector::inNamespace('Rekalogika\Analytics\Contracts'),
            ),
            Selector::NOT(
                Selector::inNamespace('Rekalogika\Analytics\Time'),
            ),
            Selector::NOT(
                Selector::inNamespace('Rekalogika\Analytics\Tests'),
            ),
            Selector::NOT(
                Selector::inNamespace('Rekalogika\Analytics\Exception'),
            ),
        );
    }

    public static function selectAnalyticsContracts(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('Rekalogika\Analytics\Contracts'),
        );
    }

    public static function selectAnalyticsCoreException(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('Rekalogika\Analytics\Exception'),
        );
    }
}
