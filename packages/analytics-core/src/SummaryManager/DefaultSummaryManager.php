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

namespace Rekalogika\Analytics\SummaryManager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SummaryManager\Query\SourceQuery;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template T of object
 * @implements SummaryManager<T>
 */
final readonly class DefaultSummaryManager implements SummaryManager
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        private string $class,
        private EntityManagerInterface $entityManager,
        private SummaryMetadata $metadata,
        private PropertyAccessorInterface $propertyAccessor,
        private SummaryRefresherFactory $refresherFactory,
        private int $queryResultLimit,
        private int $fillingNodesLimit,
    ) {}

    #[\Override]
    public function updateBySourceRange(
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void {
        $this->refresherFactory
            ->createSummaryRefresher($this->class)
            ->manualRefresh(
                start: $start,
                end: $end,
                batchSize: $batchSize,
                resumeId: $resumeId,
            );
    }

    #[\Override]
    public function createQuery(
        ?int $queryResultLimit = null,
        ?int $fillingNodesLimit = null,
    ): DefaultQuery {
        $dimensionChoices = array_keys($this->metadata->getLeafDimensions());
        $dimensionChoices[] = '@values';

        $measureChoices = array_keys($this->metadata->getMeasures());

        return new DefaultQuery(
            dimensionChoices: $dimensionChoices,
            measureChoices: $measureChoices,
            entityManager: $this->entityManager,
            metadata: $this->metadata,
            propertyAccessor: $this->propertyAccessor,
            queryResultLimit: $queryResultLimit ?? $this->queryResultLimit,
            fillingNodesLimit: $fillingNodesLimit ?? $this->fillingNodesLimit,
        );
    }

    #[\Override]
    public function createSourceQueryBuilder(Tuple $tuple): QueryBuilder
    {
        $summaryClass = $this->metadata->getSummaryClass();
        $tupleSummaryClass = $tuple->getSummaryTable();

        if ($summaryClass !== $tupleSummaryClass) {
            throw new InvalidArgumentException(\sprintf(
                'Summary class "%s" does not match the tuple summary class "%s".',
                $summaryClass,
                $tupleSummaryClass,
            ));
        }

        $sourceQuery = new SourceQuery(
            entityManager: $this->entityManager,
            summaryMetadata: $this->metadata,
            tuple: $tuple,
        );

        return $sourceQuery->getResult();
    }
}
