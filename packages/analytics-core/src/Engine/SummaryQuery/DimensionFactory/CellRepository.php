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

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
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
     * @var array<string,DefaultCell>
     */
    private array $signatureToCell = [];

    public function __construct(
        private readonly DimensionCollection $dimensionCollection,
        private readonly NullMeasureCollection $nullMeasureCollection,
        private readonly DefaultQuery $query,
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
        return $this->signatureToCell[$coordinates->getSignature()]
            ??= new DefaultCell(
                coordinates: $coordinates,
                measures: $this->nullMeasureCollection->getNullMeasures(),
                isNull: true,
                context: $this->context,
            );
    }

    public function getCellsByBaseAndDimension(
        DefaultCell $baseCell,
        string $dimensionName,
        mixed $dimensionMember,
    ): DefaultCell {
        $dimension = $this->dimensionCollection
            ->getDimensionsByName($dimensionName)
            ->getDimensionByMember($dimensionMember);

        if ($dimension === null) {
            throw new UnexpectedValueException(\sprintf(
                'Dimension "%s" with member "%s" not found in the dimension collection.',
                $dimensionName,
                get_debug_type($dimensionMember),
            ));
        }

        $coordinates = $baseCell->getCoordinates()->append($dimension);

        return $this->signatureToCell[$coordinates->getSignature()]
            ??= new DefaultCell(
                coordinates: $coordinates,
                measures: $this->nullMeasureCollection->getNullMeasures(),
                isNull: true,
                context: $baseCell->getContext(),
            );
    }

    /**
     * @return iterable<DefaultCell>
     */
    public function getCellsByBaseAndDimensionName(
        DefaultCell $baseCell,
        string $dimensionName,
        bool $fillGaps,
    ): iterable {
        if ($fillGaps) {
            $dimensions = $this->dimensionCollection
                ->getDimensionsByName($dimensionName)
                ->getGapFilled();
        } else {
            $dimensions = $this->dimensionCollection
                ->getDimensionsByName($dimensionName)
                ->getUnprocessed();
        }

        foreach ($dimensions as $dimension) {
            $coordinates = $baseCell->getCoordinates()->append($dimension);
            $cell = $this->signatureToCell[$coordinates->getSignature()] ?? null;

            // if the cell already exists and it is not the result of a gap fill,
            // we return it directly.
            if ($cell !== null) {
                // if fillGaps is false, we don't return cells previously
                // created due to gap filling
                if (!$fillGaps && $cell->isNull()) {
                    continue;
                }

                yield $cell;
                continue;
            }

            // if cell is null and we are not filling gaps, we skip it
            if (!$fillGaps) {
                continue;
            }

            // if cell is null and gap-filling is requestied, we create a new
            // cell

            $measures = $this->nullMeasureCollection->getNullMeasures();

            $cell = new DefaultCell(
                coordinates: $coordinates,
                measures: $measures,
                isNull: true,
                context: $baseCell->getContext(),
            );

            $this->collectCell($cell);
            yield $cell;
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
        $measures = iterator_to_array($apexMeasures, true);

        return $this->apexCell = new DefaultCell(
            coordinates: $apexCoordinates,
            measures: $apexMeasures,
            isNull: true,
            context: $this->context,
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
