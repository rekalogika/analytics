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

namespace Rekalogika\Analytics\TimeInterval;

trait TimeIntervalTrait
{
    /**
     * @var array<string,self>
     */
    private static array $cache = [];

    private int $databaseValue;

    private static function create(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ): static {
        $hash = hash('xxh128', serialize([
            static::class,
            $databaseValue,
            $timeZone->getName(),
        ]));

        return self::$cache[$hash]
            ??= new static($databaseValue, $timeZone);
    }

    public static function createFromDatabaseValue(int $databaseValue): static
    {
        $timeZone = new \DateTimeZone('UTC');

        return self::create($databaseValue, $timeZone);
    }

    abstract private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    );

    public function withTimeZone(\DateTimeZone $timeZone): static
    {
        return self::create(
            $this->databaseValue,
            $timeZone,
        );
    }

    public function getDatabaseValue(): int
    {
        return $this->databaseValue;
    }
}
