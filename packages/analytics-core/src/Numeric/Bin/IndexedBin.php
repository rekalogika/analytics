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

namespace Rekalogika\Analytics\Numeric\Bin;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\Comparable;
use Rekalogika\Analytics\Contracts\Model\DatabaseValueAware;
use Rekalogika\Analytics\Numeric\Bin\Sequence\IndexedBinSequence;
use Rekalogika\Analytics\Numeric\Binning;
use Rekalogika\Analytics\Numeric\NumericBin;
use Rekalogika\Analytics\Numeric\Util\NumberUtil;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A bin produced by a `Binning`, identified by its index. The index is the
 * value stored in the summary table.
 *
 * There is only one instance for each binning and index at any time, so that
 * the instances can be compared by identity.
 *
 * @implements DatabaseValueAware<int>
 */
final class IndexedBin implements
    NumericBin,
    DatabaseValueAware,
    \Stringable,
    TranslatableInterface
{
    /**
     * Weak references, so that the cache does not keep the instances alive.
     * The entry is removed by the destructor.
     *
     * @var array<string,\WeakReference<self>>
     */
    private static array $instances = [];

    private readonly string $hash;

    public static function create(Binning $binning, int $index): self
    {
        $hash = self::calculateHash($binning, $index);
        $instance = (self::$instances[$hash] ?? null)?->get();

        if ($instance !== null) {
            return $instance;
        }

        $instance = new self($binning, $index, $hash);
        self::$instances[$hash] = \WeakReference::create($instance);

        return $instance;
    }

    private static function calculateHash(Binning $binning, int $index): string
    {
        return hash('xxh128', serialize([$binning, $index]));
    }

    private function __construct(
        private readonly Binning $binning,
        private readonly int $index,
        string $hash,
    ) {
        $first = $binning->getFirstIndex();
        $last = $binning->getLastIndex();

        if (
            ($first !== null && $index < $first)
            || ($last !== null && $index > $last)
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Bin index %d is out of range of "%s".',
                $index,
                $binning::class,
            ));
        }

        $this->hash = $hash;
    }

    public function __destruct()
    {
        $instance = (self::$instances[$this->hash] ?? null)?->get();

        if ($instance === null || $instance === $this) {
            unset(self::$instances[$this->hash]);
        }
    }

    public function getBinning(): Binning
    {
        return $this->binning;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    #[\Override]
    public function getDatabaseValue(): int
    {
        return $this->index;
    }

    #[\Override]
    public function getLowerBound(): int|float|null
    {
        return $this->binning->getLowerBound($this->index);
    }

    #[\Override]
    public function getUpperBound(): int|float|null
    {
        return $this->binning->getUpperBound($this->index);
    }

    #[\Override]
    public function getNext(): ?static
    {
        $last = $this->binning->getLastIndex();

        if ($last !== null && $this->index >= $last) {
            return null;
        }

        return self::create($this->binning, $this->index + 1);
    }

    #[\Override]
    public function getPrevious(): ?static
    {
        $first = $this->binning->getFirstIndex();

        if ($first !== null && $this->index <= $first) {
            return null;
        }

        return self::create($this->binning, $this->index - 1);
    }

    /**
     * Returns the sequence of all the bins if the binning is bounded on both
     * sides, otherwise null.
     *
     * @return IndexedBinSequence|null
     */
    #[\Override]
    public function getSequence(): ?IndexedBinSequence
    {
        $first = $this->binning->getFirstIndex();
        $last = $this->binning->getLastIndex();

        if ($first === null || $last === null) {
            return null;
        }

        return new IndexedBinSequence(
            first: self::create($this->binning, $first),
            last: self::create($this->binning, $last),
        );
    }

    /**
     * @return -1|0|1
     */
    #[\Override]
    public static function compare(Comparable $a, Comparable $b): int
    {
        if (
            !$a instanceof self
            || !$b instanceof self
            || serialize($a->binning) !== serialize($b->binning)
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Cannot compare "%s" with "%s".',
                get_debug_type($a),
                get_debug_type($b),
            ));
        }

        return $a->index <=> $b->index;
    }

    /**
     * The interval in the mathematical notation, e.g. `[26, 36)`, `< 26`, or
     * `≥ 66`.
     */
    #[\Override]
    public function __toString(): string
    {
        $lower = $this->getLowerBound();
        $upper = $this->getUpperBound();

        if ($lower === null) {
            return $upper === null ? '*' : '< ' . NumberUtil::format($upper);
        }

        if ($upper === null) {
            return '≥ ' . NumberUtil::format($lower);
        }

        return \sprintf(
            '[%s, %s)',
            NumberUtil::format($lower),
            NumberUtil::format($upper),
        );
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->__toString();
    }
}
