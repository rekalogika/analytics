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

namespace Rekalogika\Analytics\Time\Bin\Sequence;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Model\Sequence;
use Rekalogika\Analytics\Time\RecurringTimeBin;

/**
 * @template T of RecurringTimeBin
 * @implements Sequence<T>
 * @implements \IteratorAggregate<T>
 */
final readonly class RecurringTimeBinSequence implements Sequence, \IteratorAggregate
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
            throw new LogicException(
                'The sequence is empty, cannot create a sequence from an empty bin.',
            );
        }

        $this->cases = $cases;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->cases;
    }

    #[\Override]
    public function getFirst(): RecurringTimeBin
    {
        return $this->cases[0];
    }

    #[\Override]
    public function getLast(): RecurringTimeBin
    {
        return $this->cases[\count($this->cases) - 1];
    }
}
