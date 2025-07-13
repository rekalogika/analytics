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
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Engine\RefreshAgent\DefaultRefreshAgentStrategy;
use Rekalogika\Analytics\Engine\SummaryManager\SummaryRefresherFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rekalogika:analytics:refresh',
    description: 'Refresh dirty partitions of a summary table.',
)]
final class RefreshCommand extends Command
{
    private ?SymfonyStyle $io = null;

    public function __construct(
        private readonly RefreshCommandOutputEventSubscriber $refreshCommandOutputEventSubscriber,
        private readonly SummaryRefresherFactory $summaryRefresherFactory,
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
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

        $class = $input->getArgument('class') ?? $this->io->choice(
            question: 'Please select the summary class to refresh',
            choices: $this->summaryMetadataFactory->getSummaryClasses(),
            default: null,
        );

        /** @psalm-suppress TypeDoesNotContainType */
        if (!\is_string($class)) {
            throw new UnexpectedValueException(\sprintf(
                'Class name must be a string, got "%s".',
                get_debug_type($class),
            ));
        }

        /** @psalm-suppress TypeDoesNotContainType */
        if (!class_exists($class)) {
            throw new UnexpectedValueException(\sprintf(
                'Class "%s" not found.',
                $class,
            ));
        }

        $strategy = new DefaultRefreshAgentStrategy(
            minimumAge: null,
            maximumAge: null,
            minimumIdleDelay: null,
        );

        $this->summaryRefresherFactory
            ->createSummaryRefresher($class)
            ->refresh($strategy, maxIterations: null);

        return Command::SUCCESS;
    }
}
