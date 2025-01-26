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

namespace Rekalogika\Analytics\SummaryManager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\ReversibleValueResolver;
use Rekalogika\Analytics\ValueRangeResolver;

/**
 * Roll up lower level summary to higher level by grouping by the entire row set
 */
final class SourceIdRangeDeterminer extends AbstractQuery
{
    private readonly ReversibleValueResolver $valueResolver;

    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClassMetadataWrapper $doctrineMetadata,
        private readonly SummaryMetadata $summaryMetadata,
    ) {
        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->from($class, 'e');

        parent::__construct($queryBuilder);

        $this->valueResolver = $this->summaryMetadata
            ->getPartition()
            ->getSource()[$class]
            ?? throw new \InvalidArgumentException(\sprintf(
                'Value resolver for class "%s" not found',
                $class,
            ));
    }

    public function getMinId(): int|string|null
    {
        $result = $this->getQueryBuilder()
            ->select($this->getMinDQL())
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null || \is_int($result)) {
            return $result;
        }

        if (is_numeric($result)) {
            return (int) $result;
        }

        return (string) $result;
    }

    public function getMaxId(): int|string|null
    {
        $result = $this->getQueryBuilder()
            ->select($this->getMaxDQL())
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null || \is_int($result)) {
            return $result;
        }

        if (is_numeric($result)) {
            return (int) $result;
        }

        return (string) $result;
    }

    private function getMinDQL(): string
    {
        $valueResolver = $this->valueResolver;

        if ($valueResolver instanceof ValueRangeResolver) {
            return $valueResolver->getMinDQL($this->getQueryContext());
        }

        return $this->getDefaultMinDQL();
    }

    private function getMaxDQL(): string
    {
        $valueResolver = $this->valueResolver;

        if ($valueResolver instanceof ValueRangeResolver) {
            return $valueResolver->getMaxDQL($this->getQueryContext());
        }

        return $this->getDefaultMaxDQL();
    }

    private function getDefaultMinDQL(): string
    {
        return \sprintf(
            'MIN(e.%s)',
            $this->doctrineMetadata->getIdentifierFieldName(),
        );
    }

    private function getDefaultMaxDQL(): string
    {
        return \sprintf(
            'MAX(e.%s)',
            $this->doctrineMetadata->getIdentifierFieldName(),
        );
    }
}
