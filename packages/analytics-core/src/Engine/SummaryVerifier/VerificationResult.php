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

namespace Rekalogika\Analytics\Engine\SummaryVerifier;

use Rekalogika\Analytics\Contracts\Model\Partition;

/**
 * The outcome of the verification of a summary, by lowest partition.
 */
final class VerificationResult
{
    private int $verifiedCount = 0;

    /**
     * @var list<Partition>
     */
    private array $mismatched = [];

    /**
     * @var list<Partition>
     */
    private array $skipped = [];

    /**
     * @internal
     */
    public function addVerified(): void
    {
        $this->verifiedCount++;
    }

    /**
     * @internal
     */
    public function addMismatched(Partition $partition): void
    {
        $this->mismatched[] = $partition;
    }

    /**
     * @internal
     */
    public function addSkipped(Partition $partition): void
    {
        $this->skipped[] = $partition;
    }

    /**
     * The number of partitions whose stored rows match the source.
     */
    public function getVerifiedCount(): int
    {
        return $this->verifiedCount;
    }

    /**
     * The partitions whose stored rows differ from the source.
     *
     * @return list<Partition>
     */
    public function getMismatched(): array
    {
        return $this->mismatched;
    }

    /**
     * The partitions that were not compared, because they have a pending
     * dirty flag, or are not completely summarized yet.
     *
     * @return list<Partition>
     */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    public function isValid(): bool
    {
        return $this->mismatched === [];
    }
}
