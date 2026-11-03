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

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Rekalogika\Analytics\Metadata\Source\AssociationDependency;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\Util\InvolvedPropertyUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Shows which changes to a source entity are detected, i.e. make the
 * summaries of the entity refresh the affected partitions.
 */
#[AsCommand(
    name: 'rekalogika:analytics:debug:source',
    description: 'Displays the changes to a source class that refresh its summaries',
)]
final class DebugSourceCommand extends Command
{
    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly SourceMetadataFactory $sourceMetadataFactory,
        private readonly ManagerRegistry $managerRegistry,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: 'sourceClass',
            mode: InputArgument::OPTIONAL,
            description: 'Source class name. If omitted, every source class is shown.',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var mixed */
        $class = $input->getArgument('sourceClass');

        if ($class === null) {
            foreach ($this->getSourceClasses() as $sourceClass) {
                $this->printSourceClass($io, $sourceClass);
            }

            return Command::SUCCESS;
        }

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

        $this->printSourceClass($io, $class);

        return Command::SUCCESS;
    }

    /**
     * The source classes of the summaries, and the classes read by the
     * summaries through an association.
     *
     * @return list<class-string>
     */
    private function getSourceClasses(): array
    {
        $sourceClasses = [];

        foreach ($this->getAllSummaryMetadata() as $summaryMetadata) {
            $sourceClasses[] = $summaryMetadata->getSourceClass();
        }

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                $sourceMetadata = $this->sourceMetadataFactory
                    ->getSourceMetadata($classMetadata->getName());

                foreach ($sourceMetadata->getAllAssociationDependencies() as $dependency) {
                    $sourceClasses[] = $dependency->getRelatedClass();
                }
            }
        }

        $sourceClasses = array_values(array_unique($sourceClasses));
        sort($sourceClasses);

        return $sourceClasses;
    }

    /**
     * @return iterable<SummaryMetadata>
     */
    private function getAllSummaryMetadata(): iterable
    {
        foreach ($this->summaryMetadataFactory->getSummaryClasses() as $summaryClass) {
            yield $this->summaryMetadataFactory->getSummaryMetadata($summaryClass);
        }
    }

    /**
     * @param class-string $sourceClass
     */
    private function printSourceClass(SymfonyStyle $io, string $sourceClass): void
    {
        $io->title('Source Class ' . $sourceClass);

        $sourceMetadata = $this->sourceMetadataFactory
            ->getSourceMetadata($sourceClass);

        $propertyToSummaryClasses = $sourceMetadata->getPropertyToSummaryClasses();
        $associationDependencies = $sourceMetadata->getAllAssociationDependencies();

        if ($propertyToSummaryClasses === [] && $associationDependencies === []) {
            $io->warning('No summary depends on this class.');

            return;
        }

        if ($propertyToSummaryClasses !== []) {
            $this->printTrackedProperties($io, $sourceClass, $propertyToSummaryClasses);
            $this->printUntrackedRelatedPaths($io, $sourceClass);
        }

        if ($associationDependencies !== []) {
            $this->printAssociationDependencies($io, $associationDependencies);
        }
    }

    /**
     * The summaries of other classes that read this class through an
     * association.
     *
     * @param list<AssociationDependency> $dependencies
     */
    private function printAssociationDependencies(
        SymfonyStyle $io,
        array $dependencies,
    ): void {
        $io->section('Read Through Associations');
        $io->text([
            'A change to one of these properties refreshes the source entities referring to the entity through the path.',
            'The deletion of the entity refreshes the source entities referring to it through any path.',
        ]);
        $io->newLine();

        /** @var array<string,array{string,class-string,string}> */
        $rows = [];

        /** @var array<string,list<class-string>> */
        $summaryClasses = [];

        foreach ($dependencies as $dependency) {
            $property = $dependency->getProperty() ?? '(deletion)';

            $key = implode('|', [
                $property,
                $dependency->getSourceClass(),
                $dependency->getReversePath(),
            ]);

            $rows[$key] = [$property, $dependency->getSourceClass(), $dependency->getReversePath()];
            $summaryClasses[$key][] = $dependency->getSummaryClass();
        }

        ksort($rows);

        $io->table(
            headers: ['Property', 'Source Class', 'Path', 'Summaries'],
            rows: array_map(
                fn(string $key, array $row): array => [
                    ...$row,
                    $this->formatSummaryClasses($summaryClasses[$key] ?? []),
                ],
                array_keys($rows),
                array_values($rows),
            ),
        );
    }

    /**
     * @param class-string $sourceClass
     * @param array<string,list<class-string>> $propertyToSummaryClasses
     */
    private function printTrackedProperties(
        SymfonyStyle $io,
        string $sourceClass,
        array $propertyToSummaryClasses,
    ): void {
        $io->section('Tracked Properties');
        $io->text('A change to one of these properties refreshes the summaries.');
        $io->newLine();

        ksort($propertyToSummaryClasses);
        $rows = [];

        foreach ($propertyToSummaryClasses as $property => $summaryClasses) {
            // without the Doctrine mapping, the metadata cannot tell
            // `assoc.prop` from `embedded.prop` and registers both. only show
            // the ones that exist

            if (!$this->isMappedProperty($sourceClass, $property)) {
                continue;
            }

            $rows[] = [$property, $this->formatSummaryClasses($summaryClasses)];
        }

        $io->table(
            headers: ['Property', 'Summaries'],
            rows: $rows,
        );
    }

    /**
     * @param list<class-string> $summaryClasses
     */
    private function formatSummaryClasses(array $summaryClasses): string
    {
        $summaryClasses = array_values(array_unique($summaryClasses));
        sort($summaryClasses);

        return implode("\n", $summaryClasses);
    }

    /**
     * @param class-string $class
     */
    private function isMappedProperty(string $class, string $property): bool
    {
        foreach ($this->getClassMetadataOfHierarchy($class) as $classMetadata) {
            if (
                $classMetadata->hasField($property)
                || $classMetadata->hasAssociation($property)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Paths of the summaries opting out of association tracking that read a
     * property of a related entity. A change to the association itself is
     * tracked, a change to the related entity is not.
     *
     * @param class-string $sourceClass
     */
    private function printUntrackedRelatedPaths(
        SymfonyStyle $io,
        string $sourceClass,
    ): void {
        /** @var array<string,string> */
        $associations = [];

        /** @var array<string,list<class-string>> */
        $summaryClasses = [];

        foreach ($this->getAllSummaryMetadata() as $summaryMetadata) {
            $summarySourceClass = $summaryMetadata->getSourceClass();

            // the summaries that see changes to this class: the summaries of
            // the class itself and of its parents

            if (
                !is_a($sourceClass, $summarySourceClass, true)
                || $summaryMetadata->isTrackAssociations()
            ) {
                continue;
            }

            foreach ($this->getRawInvolvedPaths($summaryMetadata) as $path) {
                $association = $this->getAssociationOfPath($summarySourceClass, $path);

                if ($association === null) {
                    continue;
                }

                $associations[$path] = $association;
                $summaryClasses[$path][] = $summaryMetadata->getSummaryClass();
            }
        }

        if ($associations === []) {
            return;
        }

        ksort($associations);
        $rows = [];

        foreach ($associations as $path => $association) {
            $rows[] = [
                $path,
                $association,
                $this->formatSummaryClasses($summaryClasses[$path] ?? []),
            ];
        }

        $io->section('Untracked Related Paths');
        $io->text([
            'These summaries opt out of association tracking. A change to the association is tracked,',
            'a change to the related entity is not. Use SummaryManager::markDirty() if needed.',
        ]);
        $io->newLine();

        $io->table(
            headers: ['Path', 'Tracked Association', 'Summaries'],
            rows: $rows,
        );
    }

    /**
     * The property paths as declared by the dimensions, measures and
     * partition, before being converted to change set keys.
     *
     * @return list<string>
     */
    private function getRawInvolvedPaths(SummaryMetadata $summaryMetadata): array
    {
        $paths = [];

        $propertyMetadatas = [
            ...$summaryMetadata->getRootDimensions(),
            ...$summaryMetadata->getMeasures(),
            $summaryMetadata->getPartition(),
        ];

        foreach ($propertyMetadatas as $propertyMetadata) {
            foreach ($propertyMetadata->getInvolvedSourceProperties() as $path) {
                $paths[] = trim($path);
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Returns the association the path reads through, or null if the path
     * reads a field or an embeddable of the source entity.
     *
     * @param class-string $sourceClass
     */
    private function getAssociationOfPath(string $sourceClass, string $path): ?string
    {
        // `assoc.*` reads the identity or the type of the related entity,
        // which do not change

        if (!str_contains($path, '.') || str_ends_with($path, '.*')) {
            return null;
        }

        $keys = InvolvedPropertyUtil::getChangeSetKeys($path);

        if ($keys === []) {
            return null;
        }

        $first = $keys[0];

        // the property can be declared in a subclass, if the path casts the
        // root entity

        foreach ($this->getClassMetadataOfHierarchy($sourceClass) as $classMetadata) {
            if ($classMetadata->hasAssociation($first)) {
                return $first;
            }
        }

        return null;
    }

    /**
     * The metadata of the class and of its mapped subclasses.
     *
     * @param class-string $class
     * @return iterable<ClassMetadata<object>>
     */
    private function getClassMetadataOfHierarchy(string $class): iterable
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if ($manager === null) {
            return;
        }

        foreach ($manager->getMetadataFactory()->getAllMetadata() as $classMetadata) {
            if (is_a($classMetadata->getName(), $class, true)) {
                yield $classMetadata;
            }
        }
    }
}
