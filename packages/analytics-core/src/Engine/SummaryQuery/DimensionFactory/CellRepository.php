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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCoordinates;

/**
 * Collects and stores cells. Cells must be added in database order.
 */
final class CellRepository
{
    private ?DefaultCell $apexCell = null;

    /**
     * Cells from the query result.
     *
     * @var array<string,DefaultCell>
     */
    private array $signatureToCell = [];

    /**
     * Null cells created for coordinates without a row in the query result.
     * Kept separately so that they don't show up as query rows.
     *
     * @var array<string,DefaultCell>
     */
    private array $signatureToNullCell = [];

    public function __construct(
        private readonly DimensionCollection $dimensionCollection,
        private readonly NullMeasureCollection $nullMeasureCollection,
        private readonly DefaultQuery $query,
        private readonly SourceEntitiesFactory $sourceEntitiesFactory,
        private readonly ResultContext $context,
    ) {}

    public function collectCell(DefaultCell $cell): void
    {
        $this->signatureToCell[$cell->getSignature()] = $cell;

        if ($cell->getDimensionality() === []) {
            $this->apexCell = $cell;
        }
    }

    public function getCellByCoordinates(DefaultCoordinates $coordinates): DefaultCell
    {
        $cell = $this->getOrCreateCell($coordinates, $this->context);

        // preserve the dimension ordering as in the coordinates
        return $cell->withOrdering($coordinates->getDimensionNames());
    }

    /**
     * Returns the cell from the query result, or a null cell if there is no
     * row for the coordinates.
     */
    private function getOrCreateCell(
        DefaultCoordinates $coordinates,
        ResultContext $context,
    ): DefaultCell {
        $signature = $coordinates->getSignature();

        return $this->signatureToCell[$signature]
            ?? $this->signatureToNullCell[$signature]
            ??= new DefaultCell(
                coordinates: $coordinates,
                measures: $this->nullMeasureCollection->getNullMeasures(),
                isNull: true,
                context: $context,
                sourceEntitiesFactory: $this->sourceEntitiesFactory,
            );
    }

    /**
     * Returns null if the member does not occur in the result for the
     * dimension. Returns a null cell if it does, but there is no row for the
     * resulting coordinates.
     */
    public function getCellByBaseAndDimension(
        DefaultCell $baseCell,
        string $dimensionName,
        mixed $dimensionMember,
    ): ?DefaultCell {
        if (!\in_array($dimensionName, $this->query->getDimensions(), true)) {
            throw new InvalidArgumentException(\sprintf(
                'Dimension "%s" is not among the dimensions of the query.',
                $dimensionName,
            ));
        }

        $dimension = $this->dimensionCollection
            ->getDimensionsByName($dimensionName)
            ->getDimensionByMember($dimensionMember);

        if ($dimension === null) {
            return null;
        }

        $coordinates = $baseCell->getCoordinates()->append($dimension);
        $cell = $this->getOrCreateCell($coordinates, $baseCell->getContext());

        return $cell->withOrdering($coordinates->getDimensionNames());
    }

    /**
     * Note: the results do not have ordered dimensions.
     *
     * @param list<string> $dimensionNames
     * @return iterable<DefaultCell>
     */
    public function getCellsByBaseAndDimensionName(
        DefaultCell $baseCell,
        array $dimensionNames,
        bool $fillGaps,
    ): iterable {
        $dimensionName = array_shift($dimensionNames);

        if ($dimensionName === null) {
            yield $baseCell;
            return;
        }

        $dimensionFieldCollection = $this->dimensionCollection
            ->getDimensionsByName($dimensionName);

        // without gap filling, interpolated members are skipped anyway, so we
        // don't need to produce them
        $dimensions = $fillGaps
            ? $dimensionFieldCollection->getGapFilled()
            : $dimensionFieldCollection->getSorted();

        foreach ($dimensions as $dimension) {
            $coordinates = $baseCell->getCoordinates()->append($dimension);

            // if gap-filling is requested, we create a null cell for
            // coordinates without a row. otherwise, we skip them.
            $cell = $fillGaps
                ? $this->getOrCreateCell($coordinates, $baseCell->getContext())
                : $this->signatureToCell[$coordinates->getSignature()] ?? null;

            if ($cell === null) {
                continue;
            }

            $childCells = $this->getCellsByBaseAndDimensionName(
                baseCell: $cell,
                dimensionNames: $dimensionNames,
                fillGaps: $fillGaps,
            );

            foreach ($childCells as $childCell) {
                yield $childCell;
            }
        }
    }

    public function hasCellWithCoordinates(DefaultCoordinates $coordinates): bool
    {
        return isset($this->signatureToCell[$coordinates->getSignature()]);
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
        if ($this->apexCell !== null) {
            return $this->apexCell;
        }

        // no apex cell was produced, we have to create one here ourselves
        $apexCoordinates = new DefaultCoordinates(
            summaryClass: $this->query->getFrom(),
            dimensions: [],
            condition: $this->query->getDice(),
        );

        $apexMeasures = $this->nullMeasureCollection->getNullMeasures();

        return $this->apexCell = new DefaultCell(
            coordinates: $apexCoordinates,
            measures: $apexMeasures,
            isNull: true,
            context: $this->context,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
        );
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
