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

use Rekalogika\Analytics\Engine\Trigger\TriggerInstaller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rekalogika:analytics:trigger:install',
    description: 'Installs the PostgreSQL triggers that mark the summaries dirty, replacing the existing ones. For development, use a migration in production.',
)]
final class TriggerInstallCommand extends Command
{
    public function __construct(
        private readonly TriggerInstaller $triggerInstaller,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->triggerInstaller->install() as $managerName => $count) {
            $io->success(\sprintf('Executed %d statement(s) on the entity manager "%s".', $count, $managerName));
        }

        return Command::SUCCESS;
    }
}
