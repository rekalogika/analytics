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

namespace Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultTuple;

/**
 * Collects and stores cells. Cells must be added in database order.
 */
final class CellRepository
{
    private ?DefaultCell $apexCell = null;

    /**
     * @var array<string,DefaultCell>
     */
    private array $signatureToCell = [];

    public function __construct(
        private DimensionCollection $dimensionCollection,
        private NullMeasureCollection $nullMeasureCollection,
    ) {}

    public function collectCell(DefaultCell $cell): void
    {
        $this->signatureToCell[$cell->getSignature()] = $cell;

        if ($cell->getDimensionality() === []) {
            $this->apexCell = $cell;
        }
    }

    public function getCellByTuple(DefaultTuple $tuple): ?DefaultCell
    {
        return $this->signatureToCell[$tuple->getSignature()] ?? null;
    }

    /**
     * @return iterable<DefaultCell>
     */
    public function getCellsByBaseAndDimension(
        DefaultCell $baseCell,
        string $dimensionName,
    ): iterable {
        $dimensions = $this->dimensionCollection
            ->getDimensionsByName($dimensionName)
            ->getGapFilled();

        foreach ($dimensions as $dimension) {
            $tuple = $baseCell->getTuple()->append($dimension);

            // non interpolated cells

            if (isset($this->signatureToCell[$tuple->getSignature()])) {
                yield $this->signatureToCell[$tuple->getSignature()];
                continue;
            }

            // interpolated cells

            // if the member is a measure (i.e. '@values'), narrow the measure
            // names to the measure name specified in the dimension.

            /** @psalm-suppress MixedAssignment */
            $member = $dimension->getMember();

            if ($member instanceof MeasureMember) {
                $measureNames = [$member->getMeasureProperty()];
            } else {
                $measureNames = $baseCell->getMeasureNames();
            }

            $measures = $this->nullMeasureCollection->getNullMeasures();

            $cell = new DefaultCell(
                tuple: $tuple,
                measures: $measures,
                measureNames: $measureNames,
                isNull: true,
                context: $baseCell->getContext(),
            );

            yield $this->signatureToCell[$tuple->getSignature()] = $cell;
        }
    }

    public function hasCellWithTuple(DefaultTuple $tuple): bool
    {
        return isset($this->signatureToCell[$tuple->getSignature()]);
    }

    /**
     * @param list<string> $dimensionality
     * @return iterable<DefaultCell>
     */
    public function getCellsByDimensionality(array $dimensionality): iterable
    {
        sort($dimensionality);

        foreach ($this->signatureToCell as $cell) {
            if ($cell->getDimensionality() === $dimensionality) {
                yield $cell;
            }
        }
    }

    public function getApexCell(): DefaultCell
    {
        return $this->apexCell
            ?? throw new LogicException('Apex cell is not set.');
    }

    /**
     * @return iterable<DefaultCell>
     */
    public function getAllCubes(): iterable
    {
        foreach ($this->signatureToCell as $cell) {
            yield $cell;
        }
    }
}
