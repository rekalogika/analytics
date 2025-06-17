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

namespace Rekalogika\Analytics\Engine\Sequence;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Model\Sequence;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;

/**
 * @template T of SequenceMember
 * @implements Sequence<T>
 * @implements \IteratorAggregate<T>
 */
final readonly class DefaultSequence implements Sequence, \IteratorAggregate
{
    /**
     * @var class-string<T>
     */
    private string $class;

    /**
     * @param T $first
     * @param T $last
     */
    public function __construct(
        private SequenceMember $first,
        private SequenceMember $last,
    ) {
        $this->class = $first::class;

        if ($first::class !== $last::class) {
            throw new LogicException(
                'The first and last members must be of the same type.',
            );
        }
    }

    #[\Override]
    public function getFirst(): SequenceMember
    {
        return $this->first;
    }

    #[\Override]
    public function getLast(): SequenceMember
    {
        return $this->last;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        $comparison = $this->compare($this->first, $this->last);

        /** @psalm-suppress InvalidReturnStatement */
        return match ($comparison) {
            -1 => $this->getAscendingIterator(),
            0 => $this->getSingleItemIterator(),
            1 => $this->getDescendingIterator(),
        };
    }

    /**
     * @param T $a
     * @param T $b
     * @return -1|0|1
     */
    private function compare(SequenceMember $a, SequenceMember $b): int
    {
        return $this->class::compare($a, $b);
    }

    /**
     * @return \Traversable<T>
     */
    private function getSingleItemIterator(): \Traversable
    {
        yield $this->first;
    }

    /**
     * @return \Traversable<T>
     */
    private function getAscendingIterator(): \Traversable
    {
        $current = $this->first;

        while ($current !== null) {
            yield $current;

            if ($this->compare($current, $this->last) === 0) {
                break;
            }

            $current = $current->getNext();
        }
    }

    /**
     * @return \Traversable<T>
     */
    private function getDescendingIterator(): \Traversable
    {
        $current = $this->first;

        while ($current !== null) {
            yield $current;

            if ($this->compare($current, $this->last) === 0) {
                break;
            }

            $current = $current->getPrevious();
        }
    }
}
