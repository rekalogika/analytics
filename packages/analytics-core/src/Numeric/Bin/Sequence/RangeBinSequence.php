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

namespace Rekalogika\Analytics\Numeric\Bin\Sequence;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Model\Sequence;
use Rekalogika\Analytics\Numeric\RangeBin;

/**
 * All the cases of a `RangeBin` enum, in the order of declaration.
 *
 * @template T of RangeBin
 * @implements Sequence<T>
 * @implements \IteratorAggregate<T>
 */
final readonly class RangeBinSequence implements Sequence, \IteratorAggregate
{
    /**
     * @var non-empty-list<T>
     */
    private array $cases;

    /**
     * @param class-string<T> $bin
     */
    public function __construct(string $bin)
    {
        $cases = $bin::cases();

        if ($cases === []) {
            throw new LogicException(\sprintf(
                'The enum "%s" has no cases.',
                $bin,
            ));
        }

        $this->cases = $cases;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->cases;
    }

    #[\Override]
    public function getFirst(): RangeBin
    {
        return $this->cases[0];
    }

    #[\Override]
    public function getLast(): RangeBin
    {
        return $this->cases[\count($this->cases) - 1];
    }
}
