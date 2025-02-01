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

namespace Rekalogika\Analytics\Bundle\RefreshWorker;

use Psr\Log\LoggerInterface;
use Rekalogika\Analytics\RefreshWorker\RefreshCommand;
use Rekalogika\Analytics\RefreshWorker\RefreshScheduler;

final readonly class RefreshMessageHandler
{
    /**
     * @param RefreshScheduler<object> $refreshScheduler
     */
    public function __construct(
        private RefreshScheduler $refreshScheduler,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param RefreshCommand<object> $command
     */
    public function __invoke(RefreshCommand $command): void
    {
        $this->logger->info('Running refresh worker', $command->getLoggingArray());

        $this->refreshScheduler->runWorker($command);
    }
}
