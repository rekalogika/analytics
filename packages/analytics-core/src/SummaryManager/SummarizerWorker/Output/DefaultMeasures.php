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

use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Exception\InvalidArgumentException;

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
            $measuresArray[$measure->getKey()] = $measure;
        }

        $this->measures = $measuresArray;
    }

    #[\Override]
    public function get(string $key): DefaultMeasure
    {
        return $this->measures[$key]
            ?? throw new InvalidArgumentException(\sprintf(
                'Measure "%s" not found',
                $key,
            ));
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
    public function first(): ?DefaultMeasure
    {
        $firstKey = array_key_first($this->measures);

        if ($firstKey === null) {
            return null;
        }

        return $this->measures[$firstKey] ?? null;
    }
}
