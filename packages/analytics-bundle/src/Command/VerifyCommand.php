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

use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\SummaryVerifier\SummaryVerifierFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Compares the stored summary with a fresh roll-up of the source, to find
 * the partitions that are stale because a change was not detected.
 */
#[AsCommand(
    name: 'rekalogika:analytics:verify',
    description: 'Compares a summary table with its source, and reports the partitions that differ.',
)]
final class VerifyCommand extends Command
{
    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly SummaryVerifierFactory $summaryVerifierFactory,
        private readonly HandlerFactory $handlerFactory,
        private readonly SummaryManager $summaryManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: 'class',
            mode: InputArgument::REQUIRED,
            description: 'Summary class',
        );

        $this->addArgument(
            name: 'start',
            mode: InputArgument::OPTIONAL,
            description: 'Start key. If omitted, from the earliest key of the source.',
        );

        $this->addArgument(
            name: 'end',
            mode: InputArgument::OPTIONAL,
            description: 'End key. If omitted, up to the latest summarized key.',
        );

        $this->addOption(
            name: 'sample',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Verify this many randomly chosen partitions instead of a range',
        );

        $this->addOption(
            name: 'batchsize',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Number of partitions rolled up and compared in one query',
            default: 1,
        );

        $this->addOption(
            name: 'repair',
            mode: InputOption::VALUE_NONE,
            description: 'Mark the partitions that differ as dirty, so that the refresh agent refreshes them',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var mixed */
        $class = $input->getArgument('class');

        if (!\is_string($class)) {
            $io->error(\sprintf(
                'Class name must be a string, got "%s".',
                get_debug_type($class),
            ));

            return Command::FAILURE;
        }

        if (!class_exists($class)) {
            $io->error(\sprintf('Class "%s" does not exist.', $class));

            return Command::FAILURE;
        }

        if (!$this->summaryMetadataFactory->isSummary($class)) {
            $io->error(\sprintf('Class "%s" is not a summary class.', $class));

            return Command::FAILURE;
        }

        $start = $this->getKey($input->getArgument('start'));
        $end = $this->getKey($input->getArgument('end'));

        /** @var mixed */
        $sampleOption = $input->getOption('sample');
        $sample = $this->getPositiveInteger($sampleOption);
        $batchSize = $this->getPositiveInteger($input->getOption('batchsize'));

        if ($sampleOption !== null && $sample === null) {
            $io->error('The sample size must be an integer of at least 1.');

            return Command::FAILURE;
        }

        if ($batchSize === null) {
            $io->error('The batch size must be an integer of at least 1.');

            return Command::FAILURE;
        }

        if ($sample !== null && ($start !== null || $end !== null)) {
            $io->error('A sample cannot be combined with a range.');

            return Command::FAILURE;
        }

        $verifier = $this->summaryVerifierFactory->createSummaryVerifier($class);

        $result = $sample !== null
            ? $verifier->verifySample($sample)
            : $verifier->verifyRange($start, $end, $batchSize);

        $mismatched = $result->getMismatched();

        $io->definitionList(
            ['Matching partitions' => (string) $result->getVerifiedCount()],
            ['Differing partitions' => (string) \count($mismatched)],
            ['Skipped partitions, pending refresh' => (string) \count($result->getSkipped())],
        );

        if ($mismatched === []) {
            $io->success('The summary matches its source.');

            return Command::SUCCESS;
        }

        $partitionHandler = $this->handlerFactory->getSummary($class)->getPartition();
        $rows = [];
        $keys = [];

        foreach ($mismatched as $partition) {
            /** @psalm-suppress MixedAssignment */
            $lowerBound = $partitionHandler->getLowerBoundSourceValueFromPartition($partition);

            /** @psalm-suppress MixedAssignment */
            $upperBound = $partitionHandler->getUpperBoundSourceValueFromPartition($partition);

            $rows[] = [
                $partition->getLevel(),
                $partition->getKey(),
                $this->formatValue($lowerBound),
                $this->formatValue($upperBound),
            ];

            if (\is_int($lowerBound) || \is_string($lowerBound)) {
                $keys[] = $lowerBound;
            }
        }

        $io->table(
            headers: ['Level', 'Key', 'From source key', 'To source key (exclusive)'],
            rows: $rows,
        );

        if ($input->getOption('repair') === true) {
            $this->summaryManager->markDirty($class, $keys);

            $io->warning(\sprintf(
                'The summary differs from its source in %d partitions. They are marked dirty, and will be refreshed by the refresh agent.',
                \count($mismatched),
            ));
        } else {
            $io->error(\sprintf(
                'The summary differs from its source in %d partitions. Use --repair to refresh them.',
                \count($mismatched),
            ));
        }

        return Command::FAILURE;
    }

    private function getKey(mixed $key): int|string|null
    {
        if (is_numeric($key)) {
            return (int) $key;
        }

        if (\is_string($key)) {
            return $key;
        }

        return null;
    }

    private function getPositiveInteger(mixed $value): ?int
    {
        if (!\is_int($value) && !\is_string($value)) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $value === false ? null : $value;
    }

    private function formatValue(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : get_debug_type($value);
    }
}
