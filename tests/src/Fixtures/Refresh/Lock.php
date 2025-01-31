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

namespace Rekalogika\Analytics\Tests\Fixtures\Refresh;

final class Lock
{
    public function __construct(
        private readonly string $key,
        private float $ttl,
        private \DateTimeInterface $expiration,
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTtl(): float
    {
        return $this->ttl;
    }

    public function getExpiration(): \DateTimeInterface
    {
        return $this->expiration;
    }

    public function refresh(float $ttl): void
    {
        $this->ttl = $ttl;
        $this->expiration = new \DateTimeImmutable(\sprintf('+%d seconds', $ttl));
    }
}
