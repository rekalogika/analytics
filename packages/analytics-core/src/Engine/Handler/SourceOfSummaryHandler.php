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
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Handler\Query\ReferringSourcePartitionsQuery;
use Rekalogika\Analytics\Engine\Handler\Query\SourceIdRangeDeterminer;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Contracts\Service\ResetInterface;

final class SourceOfSummaryHandler implements ResetInterface
{
    /**
     * The number of related entities in a single query.
     */
    private const REFERRING_QUERY_CHUNK_SIZE = 500;

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

    /**
     * Gets the latest key from the database, bypassing the cached value, and
     * caches the result.
     */
    public function refreshLatestKey(): int|string|null
    {
        return $this->latestKey = $this->createRangeDeterminer()->getMaxKey();
    }

    public function getEarliestKey(): int|string|null
    {
        return  $this->earliestKey
            ??= $this->createRangeDeterminer()->getMinKey();
    }

    /**
     * Gets the lowest partitions containing the source entities that refer
     * to any of the given related entities through the path, e.g. the orders
     * whose `customer.country` is one of the given countries.
     *
     * @param string $reversePath The path from the source entity to the
     * related entities.
     * @param list<object> $entities The related entities.
     * @return list<Partition>
     */
    public function getLowestPartitionsReferringTo(
        string $reversePath,
        array $entities,
    ): array {
        $query = new ReferringSourcePartitionsQuery(
            entityManager: $this->entityManager,
            summaryMetadata: $this->summaryMetadata,
            reversePath: $reversePath,
        );

        $partitions = [];

        foreach (array_chunk($entities, self::REFERRING_QUERY_CHUNK_SIZE) as $chunk) {
            foreach ($query->getPartitions($chunk) as $partition) {
                $partitions[$partition->getKey()] = $partition;
            }
        }

        return array_values($partitions);
    }
}
