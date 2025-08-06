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

namespace Rekalogika\PivotTable\TableFramework;

final class DimensionRepository
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $dimensions = [];

    public function __construct(
        private readonly IdentityStrategy $identityStrategy,
    ) {}

    public function recordDimension(string $key, mixed $member): void
    {
        $signature = $this->identityStrategy->getMemberSignature($member);

        if (!isset($this->dimensions[$key][$signature])) {
            $this->dimensions[$key][$signature] = $member;
        }
    }

    /**
     * Get all dimension members for a specific key.
     *
     * @param string $key The key for which to retrieve dimensions.
     * @return list<mixed> The dimensions associated with the key.
     *
     * @throws \InvalidArgumentException If the key does not exist in the
     * dimensions.
     */
    public function getMembers(string $key): array
    {
        $dimensions = $this->dimensions[$key] ?? [];

        return array_values($dimensions);
    }
}
