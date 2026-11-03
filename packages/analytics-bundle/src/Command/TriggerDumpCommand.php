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

use Rekalogika\Analytics\Engine\Trigger\TriggerSqlGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'rekalogika:analytics:trigger:dump',
    description: 'Prints the SQL creating the PostgreSQL triggers that mark the summaries dirty, e.g. for a migration.',
)]
final class TriggerDumpCommand extends Command
{
    public function __construct(
        private readonly TriggerSqlGenerator $triggerSqlGenerator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption(
            name: 'drop',
            mode: InputOption::VALUE_NONE,
            description: 'Print the SQL dropping the triggers that exist in the database instead.',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $statementsByManager = $input->getOption('drop') === true
            ? $this->triggerSqlGenerator->getDropStatements()
            : $this->triggerSqlGenerator->getCreateStatements();

        foreach ($statementsByManager as $managerName => $statements) {
            $output->writeln(\sprintf('-- entity manager: %s', $managerName), OutputInterface::OUTPUT_RAW);
            $output->writeln('', OutputInterface::OUTPUT_RAW);

            foreach ($statements as $statement) {
                $output->writeln($statement . ';', OutputInterface::OUTPUT_RAW);
                $output->writeln('', OutputInterface::OUTPUT_RAW);
            }
        }

        return Command::SUCCESS;
    }
}
