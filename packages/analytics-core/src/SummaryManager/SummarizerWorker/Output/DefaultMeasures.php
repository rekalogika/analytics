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

use Rekalogika\Analytics\Query\Measure;
use Rekalogika\Analytics\Query\Measures;

/**
 * @implements \IteratorAggregate<string,Measure>
 */
final readonly class DefaultMeasures implements Measures, \IteratorAggregate
{
    /**
     * @var array<string,Measure>
     */
    private array $measures;

    /**
     * @param iterable<Measure> $measures
     */
    public function __construct(
        iterable $measures,
    ) {
        $measuresArray = [];

        foreach ($measures as $measure) {
            $measuresArray[$measure->getKey()] = $measure;
        }

        $this->measures = $measuresArray;
    }

    /**
     * @param iterable<Measure> $measures
     */
    public static function fromMeasures(iterable $measures): self
    {
        return new self($measures);
    }

    #[\Override]
    public function get(string $key): Measure
    {
        return $this->measures[$key]
            ?? throw new \InvalidArgumentException(\sprintf('Measure "%s" not found', $key));
    }

    #[\Override]
    public function has(string $key): bool
    {
        return isset($this->measures[$key]);
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

    #[\Override]
    public function first(): ?Measure
    {
        $firstKey = array_key_first($this->measures);

        if ($firstKey === null) {
            return null;
        }

        return $this->measures[$firstKey] ?? null;
    }
}
