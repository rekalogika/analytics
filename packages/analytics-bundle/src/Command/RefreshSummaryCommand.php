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
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Core\Exception\UnexpectedValueException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rekalogika:analytics:refresh',
    description: 'Refresh summary table.',
)]
final class RefreshSummaryCommand extends Command implements SignalableCommandInterface
{
    private ?SymfonyStyle $io = null;

    public function __construct(
        private readonly SummaryManager $summaryManager,
        private readonly RefreshCommandOutputEventSubscriber $refreshCommandOutputEventSubscriber,
    ) {
        parent::__construct();
    }

    /**
     * @return list<int>
     */
    #[\Override]
    public function getSubscribedSignals(): array
    {
        return [\SIGINT, \SIGTERM];
    }

    #[\Override]
    public function handleSignal(
        int $signal,
        int|false $previousExitCode = 0,
    ): int|false {
        if (\SIGINT !== $signal && \SIGTERM !== $signal) {
            return false;
        }

        $resumeId = $this->refreshCommandOutputEventSubscriber->getCurrentResumeId();

        if ($resumeId !== null) {
            $this->io?->warning(\sprintf('Interrupt received, stopping. To resume, add the argument --resume=%s', $resumeId));
        } else {
            $this->io?->warning('Interrupt received, stopping.');
        }

        return 1;
    }

    #[\Override] protected function configure(): void
    {
        $this->addArgument(
            name: 'class',
            mode: InputArgument::REQUIRED,
            description: 'Summary Class',
        );

        $this->addArgument(
            name: 'start',
            mode: InputArgument::OPTIONAL,
            description: 'Start key',
        );

        $this->addArgument(
            name: 'end',
            mode: InputArgument::OPTIONAL,
            description: 'End key',
        );

        $this->addOption(
            name: 'resume',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Resume identifier',
        );

        $this->addOption(
            name: 'batchsize',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Batch size',
            default: 1,
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->refreshCommandOutputEventSubscriber->initialize($this->io);

        $class = $input->getArgument('class');

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

        /** @var mixed */
        $start = $input->getArgument('start');

        /** @var mixed */
        $end = $input->getArgument('end');

        if (is_numeric($start)) {
            $start = (int) $start;
        } elseif (\is_scalar($start)) {
            /** @psalm-suppress RedundantCast */
            $start = (string) $start;
        } else {
            $start = null;
        }

        if (is_numeric($end)) {
            $end = (int) $end;
        } elseif (\is_scalar($end)) {
            /** @psalm-suppress RedundantCast */
            $end = (string) $end;
        } else {
            $end = null;
        }

        /** @var mixed */
        $resume = $input->getOption('resume');

        if ($resume !== null && !\is_string($resume)) {
            throw new InvalidArgumentException('Invalid resume ID: ' . get_debug_type($resume));
        }

        /** @var mixed */
        $batchSize = $input->getOption('batchsize');

        if (!is_numeric($batchSize)) {
            throw new InvalidArgumentException('Invalid batch size: ' . get_debug_type($batchSize));
        }

        $batchSize = (int) $batchSize;

        $this->summaryManager->refresh(
            class: $class,
            start: $start,
            end: $end,
            resumeId: $resume,
            batchSize: $batchSize,
        );

        return Command::SUCCESS;
    }
}
