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

namespace Rekalogika\Analytics\Time\Bin\Trait;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\Comparable;
use Rekalogika\Analytics\Time\Bin\Sequence\RecurringTimeBinSequence;

trait RecurringTimeBinTrait
{
    public static function createFromDatabaseValue(int $databaseValue): static
    {
        return self::from($databaseValue);
    }

    /**
     * @return -1|0|1
     */
    public static function compare(
        Comparable $a,
        Comparable $b,
    ): int {
        if (
            $a::class !== $b::class
            || !$a instanceof static // @phpstan-ignore instanceof.alwaysTrue
            || !$b instanceof static // @phpstan-ignore instanceof.alwaysTrue
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Cannot compare "%s" with "%s".',
                $a::class,
                $b::class,
            ));
        }

        /** @psalm-suppress NoInterfaceProperties */
        return $a->value <=> $b->value;
    }

    #[\Override]
    public function getNext(): ?static
    {
        return self::tryFrom($this->value + 1);
    }

    #[\Override]
    public function getPrevious(): ?static
    {
        return self::tryFrom($this->value - 1);
    }

    /**
     * @psalm-suppress InvalidReturnType
     * @return RecurringTimeBinSequence<static>
     */
    #[\Override]
    public function getSequence(): RecurringTimeBinSequence
    {
        return new RecurringTimeBinSequence(static::class);
    }
}
