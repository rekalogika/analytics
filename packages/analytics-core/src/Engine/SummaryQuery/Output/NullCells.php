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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Rekalogika\Analytics\Contracts\Result\CubeCells;

/**
 * @implements \IteratorAggregate<DefaultCoordinates,DefaultCell>
 */
final class NullCells implements CubeCells, \IteratorAggregate
{
    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from [];
    }

    #[\Override]
    public function get(mixed $key): ?DefaultCell
    {
        return null;
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultCell
    {
        return null;
    }

    #[\Override]
    public function has(mixed $key): bool
    {
        return false;
    }

    #[\Override]
    public function first(): ?DefaultCell
    {
        return null;
    }

    #[\Override]
    public function last(): ?DefaultCell
    {
        return null;
    }

    #[\Override]
    public function count(): int
    {
        return 0;
    }
}
