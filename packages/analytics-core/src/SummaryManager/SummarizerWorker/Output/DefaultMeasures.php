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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Result\Measure;
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
    public function getByName(string $name): ?DefaultMeasure
    {
        return $this->measures[$name] ?? null;
    }

    #[\Override]
    public function getByIndex(int $index): ?Measure
    {
        $keys = array_keys($this->measures);

        if (!isset($keys[$index])) {
            return null;
        }

        $name = $keys[$index];

        return $this->measures[$name] ?? null;
    }

    #[\Override]
    public function has(string $name): bool
    {
        return isset($this->measures[$name]);
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
