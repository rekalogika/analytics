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

namespace Rekalogika\Analytics\RefreshWorker;

use Rekalogika\Analytics\Partition;

/**
 * @template L of object The lock object used by the framework.
 */
final readonly class RefreshCommand
{
    /**
     * @param class-string $class
     * @param ?Partition $partition Empty partition means to process new
     * records.
     * @param L $key
     */
    public function __construct(
        private bool $primary,
        private string $class,
        private ?Partition $partition,
        private object $key,
    ) {}

    /**
     * @return array<string,scalar>
     */
    public function getLoggingArray(): array
    {
        return [
            'primary' => $this->primary,
            'class' => $this->class,
            'partition' => (string) $this->partition,
        ];
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getPartition(): ?Partition
    {
        return $this->partition;
    }

    /**
     * @return L
     */
    public function getKey(): object
    {
        return $this->key;
    }
}
