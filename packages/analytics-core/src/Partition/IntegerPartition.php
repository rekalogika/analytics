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

namespace Rekalogika\Analytics\Partition;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Attribute\PartitionKey;
use Rekalogika\Analytics\Attribute\PartitionLevel;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\PartitionKeyClassifier\BigIntClassifier;

/**
 * Partition for summarizing source entities with integer primary key.
 */
#[Embeddable]
abstract class IntegerPartition implements Partition, \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        /**
         * @psalm-suppress PossiblyFalseOperand
         * @phpstan-ignore variable.undefined
         */
        $shortClassName = substr(static::class, strrpos(static::class, '\\') + 1);

        return \sprintf(
            '%s(%d,%d)',
            $shortClassName,
            $this->level,
            $this->key,
        );
    }

    final protected function __construct(
        /**
         * First number in the partition
         */
        #[Column(type: Types::BIGINT, nullable: false)]
        #[PartitionKey(
            classifier: new BigIntClassifier(),
        )]
        protected int $key,
        /**
         * Number of insignificant/zero bits of the integer stored in the `key`
         * field. The number of the significant bits is 64 - this value.
         */
        #[Column(type: Types::SMALLINT, nullable: false)]
        #[PartitionLevel]
        protected int $level,
    ) {}

    #[\Override]
    public function getKey(): int
    {
        return $this->key;
    }

    #[\Override]
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Number of don't care bits for each of the partitioning levels, from the
     * highest to the lowest.
     */
    #[\Override]
    abstract public static function getAllLevels(): array;

    #[\Override]
    public static function createFromSourceValue(mixed $source, int $level): static
    {
        if (!is_numeric($source)) {
            throw new InvalidArgumentException(\sprintf('Source value must be an integer. Got: "%s"', get_debug_type($source)));
        }

        $source = (int) $source;

        if (!\in_array($level, static::getAllLevels(), true)) {
            throw new InvalidArgumentException(\sprintf(
                'Level "%d" is not valid. Valid levels are: %s',
                $level,
                implode(', ', static::getAllLevels()),
            ));
        }

        $source &= ~((1 << $level) - 1);

        // @phpstan-ignore new.staticInAbstractClassStaticMethod
        return new static($source, $level);
    }

    #[\Override]
    public function getLowerBound(): int
    {
        return $this->key & ~((1 << $this->level) - 1);
    }

    #[\Override]
    public function getUpperBound(): int
    {
        return ($this->key | ((1 << $this->level) - 1)) + 1;
    }

    #[\Override]
    public function getContaining(): ?static
    {
        $levels = static::getAllLevels();
        $key = array_search($this->level, $levels, true);

        if ($key === false) {
            throw new UnexpectedValueException(\sprintf(
                'Level "%d" not found in the list of levels: %s',
                $this->level,
                implode(', ', $levels),
            ));
        }

        $previousLevelKey = $key - 1;

        if ($previousLevelKey < 0) {
            return null;
        }

        $level = $levels[$previousLevelKey];

        return static::createFromSourceValue($this->getLowerBound(), $level);
    }

    #[\Override]
    public function getNext(): ?static
    {
        return static::createFromSourceValue(
            source: $this->getUpperBound(),
            level: $this->getLevel(),
        );
    }

    #[\Override]
    public function getPrevious(): ?static
    {
        if ($this->getLowerBound() === 0) {
            return null;
        }

        return static::createFromSourceValue(
            source: $this->getLowerBound() - 1,
            level: $this->getLevel(),
        );
    }
}
