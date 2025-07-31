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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Result\NormalTable;

/**
 * @implements \IteratorAggregate<int,DefaultNormalRow>
 */
final readonly class DefaultNormalTable implements NormalTable, \IteratorAggregate
{
    /**
     * @var array<string,DefaultNormalRow>
     */
    private array $rows;

    /**
     * @param class-string $summaryClass
     * @param iterable<DefaultNormalRow> $rows
     */
    public function __construct(
        private string $summaryClass,
        iterable $rows,
    ) {
        $newRows = [];

        foreach ($rows as $row) {
            $newRows[$row->getSignature()] = $row;
        }

        $this->rows = $newRows;
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getByKey(mixed $key): ?DefaultNormalRow
    {
        if (!$key instanceof DefaultTuple) {
            throw new InvalidArgumentException('This table only supports DefaultTuple as key');
        }

        $signature = $key->getSignature();

        return $this->rows[$signature] ?? null;
    }

    #[\Override]
    public function getByIndex(int $index): mixed
    {
        $keys = array_keys($this->rows);

        if (!isset($keys[$index])) {
            return null;
        }

        $signature = $keys[$index];

        return $this->rows[$signature] ?? null;
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        if (!$key instanceof DefaultTuple) {
            throw new InvalidArgumentException('This table only supports DefaultTuple as key');
        }

        $signature = $key->getSignature();

        return isset($this->rows[$signature]);
    }

    #[\Override]
    public function first(): ?DefaultNormalRow
    {
        $firstKey = array_key_first($this->rows);

        if ($firstKey === null) {
            return null;
        }

        return $this->rows[$firstKey];
    }

    #[\Override]
    public function last(): ?DefaultNormalRow
    {
        $lastKey = array_key_last($this->rows);

        if ($lastKey === null) {
            return null;
        }

        return $this->rows[$lastKey];
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->rows);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->rows as $row) {
            yield $row;
        }
    }
}
