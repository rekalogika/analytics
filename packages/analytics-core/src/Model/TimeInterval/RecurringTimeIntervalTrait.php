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

namespace Rekalogika\Analytics\Model\TimeInterval;

use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Exception\InvalidArgumentException;

trait RecurringTimeIntervalTrait
{
    public static function createFromDatabaseValue(int $databaseValue): static
    {
        return self::from($databaseValue);
    }

    /**
     * @return -1|0|1
     */
    public static function compare(
        SequenceMember $a,
        SequenceMember $b,
    ): int {
        if (
            $a::class !== $b::class
            || !$a instanceof static
            || !$b instanceof static
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
}
