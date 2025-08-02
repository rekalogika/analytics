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

namespace Rekalogika\Analytics\Engine\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\Handler\Query\SourceIdRangeDeterminer;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Contracts\Service\ResetInterface;

final class SourceOfSummaryHandler implements ResetInterface
{
    private int|string|null $latestKey = null;
    private int|string|null $earliestKey = null;

    /**
     * @var class-string $sourceClass
     */
    private readonly string $sourceClass;

    public function __construct(
        private readonly SummaryMetadata $summaryMetadata,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->sourceClass = $summaryMetadata->getSourceClass();
    }

    #[\Override]
    public function reset(): void
    {
        $this->latestKey = null;
        $this->earliestKey = null;
    }

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    private function createRangeDeterminer(): SourceIdRangeDeterminer
    {
        return new SourceIdRangeDeterminer(
            class: $this->getSourceClass(),
            entityManager: $this->entityManager,
            summaryMetadata: $this->summaryMetadata,
        );
    }

    public function getLatestKey(): int|string|null
    {
        return $this->latestKey
            ??= $this->createRangeDeterminer()->getMaxKey();
    }

    public function getEarliestKey(): int|string|null
    {
        return  $this->earliestKey
            ??= $this->createRangeDeterminer()->getMinKey();
    }
}
