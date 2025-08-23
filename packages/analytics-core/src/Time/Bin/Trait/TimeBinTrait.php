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

trait TimeBinTrait
{
    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private static function create(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ): static {
        $hash = self::calculateHash($databaseValue, $timeZone);

        if (isset(self::$instances[$hash])) {
            return self::$instances[$hash];
        }

        $instance = new static($databaseValue, $timeZone);

        return self::$instances[$hash] = $instance;
    }

    private static function calculateHash(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ): string {
        return hash('xxh128', serialize([
            static::class,
            $databaseValue,
            $timeZone->getName(),
        ]));
    }

    public static function createFromDatabaseValue(int $databaseValue): static
    {
        $timeZone = new \DateTimeZone('UTC');

        return self::create($databaseValue, $timeZone);
    }

    final private function __construct(
        protected readonly int $databaseValue,
        protected readonly \DateTimeZone $timeZone,
    ) {
        $this->initialize();
    }

    /**
     * Initialize the time bin. This method is called from the constructor.
     */
    abstract private function initialize(): void;

    public function __destruct()
    {
        $hash = self::calculateHash($this->databaseValue, $this->timeZone);

        if (isset(self::$instances[$hash])) {
            unset(self::$instances[$hash]);
        }
    }

    public function withTimeZone(\DateTimeZone $timeZone): static
    {
        return self::create($this->databaseValue, $timeZone);
    }

    public function getDatabaseValue(): int
    {
        return $this->databaseValue;
    }

    public function getSequence(): null
    {
        return null;
    }

    /**
     * @return -1|0|1
     */
    public static function compare(
        Comparable $a,
        Comparable $b,
    ): int {
        if (
            !$a instanceof self
            || !$b instanceof self
            // @phpstan-ignore notIdentical.alwaysFalse
            || $a::class !== $b::class
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Cannot compare "%s" with "%s".',
                $a::class,
                $b::class,
            ));
        }

        return $a->databaseValue <=> $b->databaseValue;
    }
}
