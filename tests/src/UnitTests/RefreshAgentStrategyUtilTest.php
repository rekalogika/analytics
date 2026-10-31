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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Engine\RefreshAgent\DefaultRefreshAgentStrategy;
use Rekalogika\Analytics\Engine\Util\RefreshAgentStrategyUtil;

final class RefreshAgentStrategyUtilTest extends TestCase
{
    /**
     * Times are in seconds after the earliest dirty flag
     */
    #[DataProvider('refreshTimeProvider')]
    public function testRefreshTime(
        ?int $minimumAge,
        ?int $minimumIdleDelay,
        ?int $maximumAge,
        int $latest,
        int $expected,
    ): void {
        $strategy = new DefaultRefreshAgentStrategy(
            minimumAge: $minimumAge,
            minimumIdleDelay: $minimumIdleDelay,
            maximumAge: $maximumAge,
        );

        $earliest = new \DateTimeImmutable('2030-01-01 00:00:00');

        $refreshTime = RefreshAgentStrategyUtil::getRefreshTime(
            refreshAgentStrategy: $strategy,
            earliest: $earliest,
            latest: $earliest->modify(\sprintf('+%d seconds', $latest)),
        );

        $this->assertEquals(
            $earliest->modify(\sprintf('+%d seconds', $expected)),
            $refreshTime,
        );
    }

    /**
     * @return iterable<string,array{?int,?int,?int,int,int}>
     */
    public static function refreshTimeProvider(): iterable
    {
        // minimum age, minimum idle delay, maximum age, latest, expected

        yield 'single change, minimum age' => [600, 300, 1800, 0, 600];
        yield 'idle delay after minimum age' => [600, 300, 1800, 500, 800];
        yield 'continuous changes, maximum age' => [600, 300, 1800, 1700, 1800];
        yield 'no minimum age' => [null, 300, 1800, 100, 400];
        yield 'no idle delay' => [600, null, 1800, 1700, 600];
        yield 'no maximum age' => [600, 300, null, 5000, 5300];
        yield 'maximum age below minimum age' => [600, 300, 100, 500, 600];
        yield 'nothing' => [null, null, null, 500, 0];
    }
}
