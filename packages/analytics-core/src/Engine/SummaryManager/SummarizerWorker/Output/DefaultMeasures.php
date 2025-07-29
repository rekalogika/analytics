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

use Rekalogika\Analytics\Contracts\Result\Measures;

/**
 * @implements \IteratorAggregate<string,DefaultMeasure>
 */
final readonly class DefaultMeasures implements Measures, \IteratorAggregate
{
    /**
     * @var array<string,DefaultMeasure>
     */
    private array $measures;

    /**
     * @param iterable<DefaultMeasure> $measures
     */
    public function __construct(
        iterable $measures,
    ) {
        $measuresArray = [];

        foreach ($measures as $measure) {
            $measuresArray[$measure->getName()] = $measure;
        }

        $this->measures = $measuresArray;
    }

    #[\Override]
    public function getByKey(mixed $key): ?DefaultMeasure
    {
        return $this->measures[$key] ?? null;
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultMeasure
    {
        $keys = array_keys($this->measures);

        if (!isset($keys[$index])) {
            return null;
        }

        $name = $keys[$index];

        return $this->measures[$name] ?? null;
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        return isset($this->measures[$key]);
    }

    #[\Override]
    public function first(): ?DefaultMeasure
    {
        $keys = array_keys($this->measures);
        return $keys ? $this->measures[$keys[0]] : null;
    }

    #[\Override]
    public function last(): ?DefaultMeasure
    {
        $keys = array_keys($this->measures);
        return $keys ? $this->measures[end($keys)] : null;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->measures);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->measures;
    }
}
