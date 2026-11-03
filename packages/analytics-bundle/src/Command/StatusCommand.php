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

use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStrategy;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rekalogika:analytics:status',
    description: 'Displays the refresh status of the summary tables.',
)]
final class StatusCommand extends Command
{
    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly HandlerFactory $handlerFactory,
        private readonly RefreshAgentStrategy $refreshAgentStrategy,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: 'class',
            mode: InputArgument::OPTIONAL,
            description: 'Summary class. If omitted, every summary class is shown.',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var mixed */
        $class = $input->getArgument('class');

        if ($class === null) {
            $classes = $this->summaryMetadataFactory->getSummaryClasses();
            sort($classes);
        } elseif (!\is_string($class)) {
            $io->error(\sprintf(
                'Class name must be a string, got "%s".',
                get_debug_type($class),
            ));

            return Command::FAILURE;
        } elseif (!class_exists($class)) {
            $io->error(\sprintf('Class "%s" does not exist.', $class));

            return Command::FAILURE;
        } elseif (!$this->summaryMetadataFactory->isSummary($class)) {
            $io->error(\sprintf('Class "%s" is not a summary class.', $class));

            return Command::FAILURE;
        } else {
            $classes = [$class];
        }

        $now = new \DateTimeImmutable();

        foreach ($classes as $summaryClass) {
            $this->printStatus($io, $summaryClass, $now);
        }

        return Command::SUCCESS;
    }

    /**
     * @param class-string $summaryClass
     */
    private function printStatus(
        SymfonyStyle $io,
        string $summaryClass,
        \DateTimeImmutable $now,
    ): void {
        $summaryHandler = $this->handlerFactory->getSummary($summaryClass);
        $dirtyFlags = $summaryHandler->getDirtyFlags();
        $flagCounts = $dirtyFlags->getDirtyFlagCounts();
        $scheduledRefresh = $summaryHandler->getScheduledRefresh();

        $nextRefreshTime = $dirtyFlags
            ->getNextRefreshTime($this->refreshAgentStrategy, $now);

        $earliest = null;
        $pending = [];

        foreach ($flagCounts as $flagCount) {
            $earliest = $earliest === null
                ? $flagCount['earliest']
                : min($earliest, $flagCount['earliest']);

            $pending[] = \sprintf(
                '%s: %d',
                $flagCount['level'] === null ? 'new records' : 'level ' . $flagCount['level'],
                $flagCount['count'],
            );
        }

        // a run that should have happened while partitions are still dirty
        // might have been lost, the sweep command dispatches it again

        $overdue = $scheduledRefresh !== null
            && $scheduledRefresh < $now
            && $nextRefreshTime !== null;

        $io->section($summaryClass);

        $io->definitionList(
            ['Dirty flags' => $pending === [] ? 'none' : implode(', ', $pending)],
            ['Earliest dirty flag' => $this->formatTime($earliest, $now)],
            ['Next refresh due' => $this->formatTime($nextRefreshTime, $now)],
            ['Scheduled refresh' => $this->formatTime($scheduledRefresh, $now) . ($overdue ? ' (overdue)' : '')],
            ['Latest summarized key' => $this->formatKey($summaryHandler->getLatestKey())],
            ['Latest source key' => $this->formatKey($summaryHandler->getSource()->getLatestKey())],
        );
    }

    private function formatTime(?\DateTimeImmutable $time, \DateTimeImmutable $now): string
    {
        if ($time === null) {
            return '-';
        }

        $seconds = $time->getTimestamp() - $now->getTimestamp();

        return \sprintf(
            '%s (%s)',
            $time->format('Y-m-d H:i:s T'),
            $seconds >= 0
                ? \sprintf('in %ds', $seconds)
                : \sprintf('%ds ago', -$seconds),
        );
    }

    private function formatKey(int|string|null $key): string
    {
        return $key === null ? '-' : (string) $key;
    }
}
