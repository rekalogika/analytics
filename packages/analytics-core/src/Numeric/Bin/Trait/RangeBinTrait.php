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

namespace Rekalogika\Analytics\Numeric\Bin\Trait;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\Comparable;
use Rekalogika\Analytics\Numeric\Bin\Sequence\RangeBinSequence;

/**
 * Implementation of `RangeBin`. The enum still has to implement
 * `getThresholds()`. The bins are ordered by the order of declaration of the
 * cases, not by their backing values.
 *
 * @see \Rekalogika\Analytics\Numeric\RangeBin
 */
trait RangeBinTrait
{
    private function getPosition(): int
    {
        return self::getPositionOf($this);
    }

    private static function getPositionOf(Comparable $bin): int
    {
        foreach (self::cases() as $position => $case) {
            if ($case === $bin) {
                return $position;
            }
        }

        // @codeCoverageIgnoreStart
        throw new InvalidArgumentException('Case not found.');
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return -1|0|1
     */
    public static function compare(
        Comparable $a,
        Comparable $b,
    ): int {
        if (
            !$a instanceof static // @phpstan-ignore instanceof.alwaysTrue
            || !$b instanceof static // @phpstan-ignore instanceof.alwaysTrue
            || $a::class !== $b::class
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Cannot compare "%s" with "%s".',
                $a::class,
                $b::class,
            ));
        }

        return self::getPositionOf($a) <=> self::getPositionOf($b);
    }

    public function getNext(): ?static
    {
        return self::cases()[$this->getPosition() + 1] ?? null;
    }

    public function getPrevious(): ?static
    {
        return self::cases()[$this->getPosition() - 1] ?? null;
    }

    /**
     * @psalm-suppress InvalidReturnType
     * @return RangeBinSequence<static>
     */
    public function getSequence(): RangeBinSequence
    {
        return new RangeBinSequence(static::class);
    }

    public function getLowerBound(): int|float|null
    {
        return self::getThresholds()[$this->getPosition() - 1] ?? null;
    }

    public function getUpperBound(): int|float|null
    {
        return self::getThresholds()[$this->getPosition()] ?? null;
    }
}
