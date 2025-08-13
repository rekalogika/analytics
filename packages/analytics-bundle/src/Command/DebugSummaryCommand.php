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

use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresher;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherQuery;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\Util\DimensionMetadataIterator;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'rekalogika:analytics:debug:summary',
    description: 'Displays information about a summary class',
)]
final class DebugSummaryCommand extends Command
{
    private ?SymfonyStyle $io = null;

    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly SummaryRefresherFactory $summaryRefresherFactory,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: 'summaryClass',
            mode: InputArgument::OPTIONAL,
            description: 'Summary class name',
        );

        // add optional --query option
        $this->addOption(
            name: 'query',
            mode: InputOption::VALUE_NONE,
            description: 'If set, the command will show the SQL query various operations.',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $class = $input->getArgument('summaryClass') ?? $io->choice(
            question: 'Please select the summary class to view',
            choices: $this->summaryMetadataFactory->getSummaryClasses(),
            default: null,
        );

        $showQueries = $input->getOption('query');

        if (!\is_string($class) || !class_exists($class)) {
            $this->io = new SymfonyStyle($input, $output);
            $this->io->error('Class does not exist.');
            return Command::FAILURE;
        }

        if ($showQueries) {
            $this->printQueries($io, $class);
        } else {
            $this->printGeneralInformation($io, $class);
        }

        return Command::SUCCESS;
    }

    /**
     * @param class-string $summaryClass
     */
    private function printGeneralInformation(
        SymfonyStyle $io,
        string $summaryClass,
    ): void {
        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $this->printHeaders(io: $io, summaryMetadata: $summaryMetadata);
        $this->printDimensions(io: $io, summaryMetadata: $summaryMetadata);
        $this->printMeasures(io: $io, summaryMetadata: $summaryMetadata);
    }

    /**
     * @param class-string $summaryClass
     */
    private function printQueries(
        SymfonyStyle $io,
        string $summaryClass,
    ): void {
        $refresher = $this->summaryRefresherFactory
            ->createSummaryRefresher($summaryClass);

        $this->printSourceToSummaryRollupSql($io, $refresher);
        $this->printSummaryToSummaryRollupSql($io, $refresher);
        $this->printDeleteSummarySql($io, $refresher);
    }

    private function printHeaders(
        SymfonyStyle $io,
        SummaryMetadata $summaryMetadata,
    ): void {
        $io->title('Summary Class Information for ' . $summaryMetadata->getSummaryClass());
        $io->section('General Information');

        $io->table(
            headers: ['Property', 'Value'],
            rows: [
                ['Summary Class', $summaryMetadata->getSummaryClass()],
                ['Source Class', $summaryMetadata->getSourceClass()],
                ['Label', $summaryMetadata->getLabel()->trans($this->translator)],
            ],
        );
    }

    private function printDimensions(
        SymfonyStyle $io,
        SummaryMetadata $summaryMetadata,
    ): void {
        $io->section('Dimensions');
        $dimensionRows = [];

        $asciiArt = $this->getDimensionAsciiArt($summaryMetadata);

        foreach ($summaryMetadata->getRootDimensions() as $dimension) {
            /** @psalm-suppress InvalidArgument */
            $dimensionRows = array_merge(
                $dimensionRows,
                iterator_to_array($this->getDimensionTableRows($dimension, 0), false),
            );
        }

        $dimensionRows = array_map(
            static fn(array $row, string $ascii) => array_merge([$ascii], $row),
            $dimensionRows,
            $asciiArt,
        );

        $io->table(
            headers: ['Property', 'Label', 'Type', 'Hidden'],
            rows: $dimensionRows,
        );
    }

    private function printMeasures(
        SymfonyStyle $io,
        SummaryMetadata $summaryMetadata,
    ): void {
        $io->section('Measures');
        $measureRows = [];

        foreach ($summaryMetadata->getMeasures() as $measure) {
            $measureRows[] = [
                $measure->getPropertyName(),
                $measure->getLabel()->trans($this->translator),
                $measure->getFunction()::class,
                $measure->isVirtual() ? 'Yes' : 'No',
                $measure->isHidden() ? 'Yes' : 'No',
            ];
        }

        $io->table(
            headers: ['Property', 'Label', 'Function', 'Virtual', 'Hidden'],
            rows: $measureRows,
        );
    }

    /**
     * @return iterable<int,list<string>>
     */
    private function getDimensionTableRows(
        DimensionMetadata $dimension,
        int $depth,
    ): iterable {
        yield [
            $dimension->getLabel()->trans($this->translator),
            $dimension->getTypeClass() ?? 'N/A',
            $dimension->isHidden() ? 'Yes' : 'No',
        ];

        $children = $dimension->getChildren();
        foreach ($children as $child) {
            yield from $this->getDimensionTableRows(
                dimension: $child,
                depth: $depth + 1,
            );
        }
    }

    /**
     * @return list<string>
     */
    private function getDimensionAsciiArt(SummaryMetadata $summaryMetadata): array
    {
        $iterator = new DimensionMetadataIterator($summaryMetadata->getRootDimensions());

        $iterator = new \RecursiveTreeIterator($iterator);
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_LEFT, '');
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, '│  ');
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_LAST, '   ');
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_HAS_NEXT, '├─ ');
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_LAST, '└─ ');
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_RIGHT, '');

        return iterator_to_array($iterator, false);
    }

    private function printDeleteSummarySql(
        SymfonyStyle $io,
        SummaryRefresher $refresher,
    ): void {
        $sqlFactory = $refresher->getSqlFactory();

        $this->printSummaryEntityQuery(
            io: $io,
            query: $sqlFactory->getDeleteExistingSummaryQuery(),
            title: 'SQL for Deleting Existing Summary Partitions',
        );
    }

    private function printSourceToSummaryRollupSql(
        SymfonyStyle $io,
        SummaryRefresher $refresher,
    ): void {
        $sqlFactory = $refresher->getSqlFactory();

        $this->printSummaryEntityQuery(
            io: $io,
            query: $sqlFactory->getRollUpSourceToSummaryQuery(),
            title: 'SQL for Rolling Up Source to Summary',
        );
    }

    private function printSummaryToSummaryRollupSql(
        SymfonyStyle $io,
        SummaryRefresher $refresher,
    ): void {
        $sqlFactory = $refresher->getSqlFactory();

        $this->printSummaryEntityQuery(
            io: $io,
            query: $sqlFactory->getRollUpSummaryToSummaryQuery(),
            title: 'SQL for Rolling Up Summary to Summary',
        );
    }

    private function printSummaryEntityQuery(
        SymfonyStyle $io,
        SummaryRefresherQuery $query,
        string $title,
    ): void {
        $io->title($title);

        foreach ($query->getQueries() as $decomposedQuery) {
            $this->printDecomposedQuery($io, $decomposedQuery);
        }
    }

    private function printDecomposedQuery(
        SymfonyStyle $io,
        DecomposedQuery $query,
    ): void {
        $io->writeln(\sprintf(
            '<info>%s;</info>',
            $query->getSql(),
        ));

        $parameters = [];
        $types = $query->getTypes();

        /** @psalm-suppress MixedAssignment */
        foreach ($query->getParameters() as $key => $value) {
            $type = $types[$key] ?? '(none)';

            // if value starts with "(placeholder)", remove it
            if (\is_string($value) && str_starts_with($value, '(placeholder) ')) {
                $value = substr($value, \strlen('(placeholder) '));
                $type = 'description of the value';
            } else {
                $value = var_export($value, true);
                $type = var_export($type, true);
            }

            $parameters[] = [
                $key,
                $value,
                $type,
            ];
        }

        $io->writeln('');
        $io->table(['Key', 'Value', 'Type'], $parameters);
    }
}
