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

namespace Rekalogika\Analytics\Contracts\Serialization;

use Rekalogika\Analytics\Contracts\Dto\CoordinatesDto;
use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\Contracts\Result\Coordinates;

/**
 * Converts the coordinates of a cell to a serializable DTO and back. This
 * allows a cell to be identified outside of a query result, e.g. in a URL
 * that links from a cell in a chart to a page listing its source entities.
 * The dimension members are serialized using the `ValueSerializer`.
 *
 * @see CoordinatesDto
 */
interface CoordinatesMapper
{
    /**
     * Converts coordinates, or the coordinates of a cell, into a DTO. The
     * special `@values` dimension is omitted.
     */
    public function toDto(Coordinates|Cell $input): CoordinatesDto;

    /**
     * Recreates the cell described by the DTO, by running a query against the
     * summary class with the dimensions, members, and predicate from the DTO.
     * The returned cell has the measures and source entities of the original
     * cell.
     *
     * @param class-string $summaryClass The summary class the coordinates
     * belong to.
     */
    public function fromDto(string $summaryClass, CoordinatesDto $dto): Cell;
}
