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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use Rekalogika\Analytics\Contracts\Dto\CoordinatesDto;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultDebugger;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Shows a cell identified by its serialized coordinates: its members, its
 * measures, and the SQL queries computing the measures from the source table
 * and from the summary table.
 */
#[AsCommand(
    name: 'rekalogika:analytics:debug:cell',
    description: 'Displays a cell identified by its serialized coordinates, and the SQL computing it',
)]
final class DebugCellCommand extends Command
{
    private const APEX_COORDINATES = '{"class":"coordinates","members":{},"predicate":null}';

    /**
     * @param Stringifier|null $stringifier Formats the members and the values
     * like the frontend does, if the frontend package is installed.
     */
    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly CoordinatesMapper $coordinatesMapper,
        private readonly SourceEntitiesFactory $sourceEntitiesFactory,
        private readonly TranslatorInterface $translator,
        private readonly ?Stringifier $stringifier = null,
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

        $this->addArgument(
            name: 'coordinates',
            mode: InputArgument::OPTIONAL,
            description: 'Serialized coordinates, as JSON. If omitted, the apex cell is shown.',
            default: self::APEX_COORDINATES,
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var mixed */
        $class = $input->getArgument('summaryClass');

        if ($class === null) {
            if (!$input->isInteractive()) {
                $io->error('The "summaryClass" argument is required in non-interactive mode.');

                return Command::FAILURE;
            }

            /** @var mixed */
            $class = $io->choice(
                question: 'Please select the summary class',
                choices: $this->summaryMetadataFactory->getSummaryClasses(),
            );
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

        if (!$this->summaryMetadataFactory->isSummary($class)) {
            $io->error(\sprintf('Class "%s" is not a summary class.', $class));

            return Command::FAILURE;
        }

        /** @var mixed */
        $serializedCoordinates = $input->getArgument('coordinates');

        if (!\is_string($serializedCoordinates)) {
            $io->error('The coordinates must be a string.');

            return Command::FAILURE;
        }

        try {
            $coordinatesDto = $this->decodeCoordinates($serializedCoordinates);
        } catch (InvalidArgumentException $e) {
            $io->error(\sprintf('Invalid coordinates: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $cell = $this->coordinatesMapper->fromDto($class, $coordinatesDto);

        $io->title('Cell Information');

        $this->printCoordinates($io, $cell);
        $this->printMeasures($io, $cell);
        $this->printPredicates($io, $cell);

        $io->section('Serialized Coordinates');
        $this->writeRaw($io, json_encode($coordinatesDto, JSON_THROW_ON_ERROR));

        $this->printSourceSql($io, $cell);
        $this->printSummarySql($io, $cell);

        return Command::SUCCESS;
    }

    private function decodeCoordinates(string $serializedCoordinates): CoordinatesDto
    {
        /** @var mixed */
        $decoded = json_decode($serializedCoordinates, true);

        if (!\is_array($decoded)) {
            throw new InvalidArgumentException(json_last_error() !== JSON_ERROR_NONE
                ? json_last_error_msg()
                : 'The coordinates must be a JSON object.');
        }

        $array = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($decoded as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidArgumentException('The coordinates must be a JSON object.');
            }

            $array[$key] = $value;
        }

        return CoordinatesDto::fromArray($array);
    }

    private function printCoordinates(SymfonyStyle $io, Cell $cell): void
    {
        $io->section('Coordinates');

        $rows = [];

        foreach ($cell->getCoordinates() as $dimension) {
            $rows[] = [
                $dimension->getName(),
                $dimension->getLabel()->trans($this->translator),
                $this->format($dimension->getMember()),
                $this->format($dimension->getRawMember()),
            ];
        }

        if ($rows === []) {
            $io->text('None, this is the apex cell.');
            $io->newLine();

            return;
        }

        $io->table(
            headers: ['Field', 'Label', 'Member', 'Raw Member'],
            rows: $rows,
        );
    }

    private function printMeasures(SymfonyStyle $io, Cell $cell): void
    {
        $io->section('Measures');

        $rows = [];

        foreach ($cell->getMeasures() as $measure) {
            $rows[] = [
                $measure->getName(),
                $measure->getLabel()->trans($this->translator),
                $this->format($measure->getValue()),
                $this->format($measure->getRawValue()),
                $this->format($measure->getUnit()),
            ];
        }

        $io->table(
            headers: ['Field', 'Label', 'Value', 'Raw Value', 'Unit'],
            rows: $rows,
        );
    }

    private function printPredicates(SymfonyStyle $io, Cell $cell): void
    {
        $io->section('Predicates');

        $predicate = $cell->getCoordinates()->getPredicate();

        if ($predicate === null) {
            $io->text('None.');
            $io->newLine();

            return;
        }

        // the predicate of the coordinates is an AND of the predicates
        $predicates = $predicate instanceof CompositeExpression
            && $predicate->getType() === CompositeExpression::TYPE_AND
            ? $predicate->getExpressionList()
            : [$predicate];

        $io->listing(array_map(
            $this->formatExpression(...),
            $predicates,
        ));
    }

    private function printSourceSql(SymfonyStyle $io, Cell $cell): void
    {
        $io->section('Source Table SQL');

        $queryComponents = $this->sourceEntitiesFactory
            ->getCoordinatesQueryComponents($cell->getCoordinates());

        $this->writeRaw($io, $queryComponents->getInterpolatedSqlStatement() . ';');
    }

    private function printSummarySql(SymfonyStyle $io, Cell $cell): void
    {
        $io->section('Summary Table SQL');

        $io->text([
            'Computes the same measures from the summary table. It covers only the source rows',
            'that have been summarized, so its result matches the source table SQL only if the',
            'summary is up to date.',
        ]);
        $io->newLine();

        if (!$cell instanceof DefaultCell) {
            $io->text('None, the cell is not in the result of the summary table.');
            $io->newLine();

            return;
        }

        $queryComponents = $cell->getSummaryQueryComponents();

        if ($queryComponents === null) {
            $io->text('None, the summary table has no data yet.');
            $io->newLine();

            return;
        }

        $this->writeRaw($io, $queryComponents->getInterpolatedSqlStatement() . ';');
    }

    /**
     * Writes the text without interpreting console formatting tags, which can
     * appear in SQL, e.g. `<foo>`.
     */
    private function writeRaw(SymfonyStyle $io, string $text): void
    {
        $io->writeln($text, OutputInterface::OUTPUT_RAW);
        $io->newLine();
    }

    private function formatExpression(Expression $expression): string
    {
        if ($expression instanceof Comparison) {
            return \sprintf(
                '%s %s %s',
                $expression->getField(),
                $expression->getOperator(),
                $this->formatValue($expression->getValue()),
            );
        }

        if ($expression instanceof CompositeExpression) {
            $parts = array_map(
                $this->formatExpression(...),
                $expression->getExpressionList(),
            );

            if ($expression->getType() === CompositeExpression::TYPE_NOT) {
                return 'NOT ' . implode(' ', $parts);
            }

            return '(' . implode(' ' . $expression->getType() . ' ', $parts) . ')';
        }

        return get_debug_type($expression);
    }

    private function formatValue(Value $value): string
    {
        /** @psalm-suppress MixedAssignment */
        $value = $value->getValue();

        if (\is_array($value)) {
            return '(' . implode(', ', array_map(
                fn(mixed $item): string => $this->formatValue(new Value($item)),
                $value,
            )) . ')';
        }

        // an integer member is an identifier, like a year, not a quantity. do
        // not let the number formatter add grouping separators.
        if (\is_int($value)) {
            return (string) $value;
        }

        return $this->format($value);
    }

    private function format(mixed $value): string
    {
        if ($this->stringifier !== null) {
            return $this->stringifier->toString($value);
        }

        if ($value === null) {
            return '(None)';
        }

        if ($value instanceof TranslatableInterface) {
            return $value->trans($this->translator);
        }

        return ResultDebugger::debugAny($value);
    }
}
