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

namespace Rekalogika\Analytics\Bundle\Command;

use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentSweeper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rekalogika:analytics:sweep',
    description: 'Dispatches the refresh agent of the summaries that have dirty partitions but no pending run.',
)]
final class SweepCommand extends Command
{
    public function __construct(
        private readonly RefreshAgentSweeper $refreshAgentSweeper,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dispatched = $this->refreshAgentSweeper->sweep();

        if ($dispatched === []) {
            $io->success('Nothing to dispatch.');

            return Command::SUCCESS;
        }

        $io->listing($dispatched);
        $io->success(\sprintf('Dispatched the refresh agent of %d summary class(es).', \count($dispatched)));

        return Command::SUCCESS;
    }
}
