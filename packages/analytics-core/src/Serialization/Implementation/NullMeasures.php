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

namespace Rekalogika\Analytics\Serialization\Implementation;

use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @implements \IteratorAggregate<string,Measure>
 */
final readonly class NullMeasures implements Measures, \IteratorAggregate
{
    /**
     * @var array<string,NullMeasure>
     */
    private array $measures;

    public function __construct(SummaryMetadata $summaryMetadata)
    {
        $measureMetadatas = $summaryMetadata->getMeasures();
        $measures = [];

        foreach (array_keys($measureMetadatas) as $name) {
            $measures[$name] = new NullMeasure($name, $summaryMetadata);
        }

        $this->measures = $measures;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->measures);
    }

    #[\Override]
    public function getByKey(mixed $key): ?NullMeasure
    {
        return $this->measures[$key] ?? null;
    }

    #[\Override]
    public function getByIndex(int $index): ?NullMeasure
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
    public function first(): ?NullMeasure
    {
        $keys = array_keys($this->measures);
        $key = $keys[0] ?? null;

        if ($key === null) {
            return null;
        }

        return $this->measures[$key];
    }

    #[\Override]
    public function last(): ?NullMeasure
    {
        $keys = array_keys($this->measures);
        $key = end($keys);

        if ($key === false) {
            return null;
        }

        return $this->measures[$key] ?? null;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->measures);
    }
}
