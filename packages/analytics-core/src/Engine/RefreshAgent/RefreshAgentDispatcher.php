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

namespace Rekalogika\Analytics\Engine\RefreshAgent;

interface RefreshAgentDispatcher
{
    /**
     * Executes `RefreshAgent::run()` method in a separate process with the
     * provided command.
     */
    public function dispatch(RefreshAgentStartCommand $command): void;
}
