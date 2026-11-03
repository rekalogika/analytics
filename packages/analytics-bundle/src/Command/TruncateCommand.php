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

use Rekalogika\Analytics\Bundle\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rekalogika:analytics:truncate',
    description: 'Truncate summary table of a specific class.',
)]
final class TruncateCommand extends Command
{
    private ?SymfonyStyle $io = null;

    public function __construct(
        private readonly RefreshCommandOutputEventSubscriber $refreshCommandOutputEventSubscriber,
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly HandlerFactory $handlerFactory,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: 'class',
            mode: InputArgument::OPTIONAL,
            description: 'Summary Class',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->refreshCommandOutputEventSubscriber->initialize($this->io);

        // Get the summary class from the input argument or prompt the user to
        // select one

        /** @var mixed */
        $class = $input->getArgument('class');

        if ($class === null) {
            if (!$input->isInteractive()) {
                $this->io->error('The "class" argument is required in non-interactive mode.');

                return Command::FAILURE;
            }

            /** @var mixed */
            $class = $this->io->choice(
                question: 'Please select the summary class to truncate',
                choices: $this->summaryMetadataFactory->getSummaryClasses(),
            );
        }

        // Validate the class name

        if (!\is_string($class)) {
            $this->io->error(\sprintf(
                'Class name must be a string, got "%s".',
                get_debug_type($class),
            ));

            return Command::FAILURE;
        }

        if (!class_exists($class)) {
            $this->io->error(\sprintf('Class "%s" not found.', $class));

            return Command::FAILURE;
        }

        // Confirm the action

        if (!$this->io->confirm(
            question: \sprintf(
                'Are you sure you want to truncate the summary table for class "%s"?',
                $class,
            ),
            default: false,
        )) {
            $this->io->warning('Truncate operation cancelled.');
            return Command::SUCCESS;
        }

        // Truncate the summary table for the specified class

        $this->handlerFactory
            ->getSummary($class)
            ->truncate();

        // Success

        $this->io->success(\sprintf(
            'Successfully truncated the summary table for class "%s".',
            $class,
        ));

        return Command::SUCCESS;
    }
}
