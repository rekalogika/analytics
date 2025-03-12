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

use Rekalogika\Analytics\Query\Dimension;
use Rekalogika\Analytics\Query\Dimensions;
use Rekalogika\Analytics\Query\Tuple;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultTuple;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;

/**
 * @implements \IteratorAggregate<string,Dimension>
 */
final readonly class DefaultTuple implements Tuple, \IteratorAggregate
{
    public function __construct(
        private Dimensions $dimensions,
    ) {}

    public static function fromResultTuple(ResultTuple $resultTuple): self
    {
        $dimensions = array_map(
            static fn(ResultValue $value): mixed => DefaultDimension::createFromResultValue($value),
            $resultTuple->getDimensions(),
        );

        $dimensions = new DefaultDimensions(
            dimensions: $dimensions,
        );

        return new self($dimensions);
    }

    #[\Override]
    public function getMembers(): array
    {
        $members = [];

        foreach ($this->dimensions as $dimension) {
            /** @psalm-suppress MixedAssignment */
            $members[$dimension->getKey()] = $dimension->getMember();
        }

        return $members;
    }

    #[\Override]
    public function get(string $key): Dimension
    {
        return $this->dimensions->get($key);
    }

    #[\Override]
    public function count(): int
    {
        return $this->dimensions->count();
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->dimensions;
    }

    #[\Override]
    public function first(): ?Dimension
    {
        return $this->dimensions->first();
    }
}
