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

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

        if (!\is_string($class) || !class_exists($class)) {
            $this->io = new SymfonyStyle($input, $output);
            $this->io->error('Class does not exist.');
            return Command::FAILURE;
        }

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($class);

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

        // dimensions

        $io->section('Dimensions');
        $dimensionRows = [];

        foreach ($summaryMetadata->getRootDimensions() as $dimension) {
            /** @psalm-suppress InvalidArgument */
            $dimensionRows = array_merge(
                $dimensionRows,
                iterator_to_array($this->getDimensionTableRows($dimension, 0), false),
            );
        }

        $io->table(
            headers: ['Property', 'Label', 'Type', 'Hidden'],
            rows: $dimensionRows,
        );

        // measures

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

        return Command::SUCCESS;
    }

    /**
     * @return iterable<int,list<string>>
     */
    private function getDimensionTableRows(
        DimensionMetadata $dimension,
        int $depth,
    ): iterable {
        $prefix = str_repeat('   ', $depth);

        yield [
            $prefix . $dimension->getPropertyName(),
            $dimension->getLabel()->trans($this->translator),
            $dimension->getTypeClass() ?? 'N/A',
            $dimension->isHidden() ? 'Yes' : 'No',
        ];

        $children = $dimension->getChildren();
        $count = \count($children);
        $i = 0;

        foreach ($children as $child) {
            $isFirst = $i === 0;
            $isLast = $i === $count - 1;

            yield from $this->getDimensionTableRows(
                dimension: $child,
                depth: $depth + 1,
            );

            $i++;
        }
    }
}
