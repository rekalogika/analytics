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
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\SummaryMetadata;

/**
 * Roll up lower level summary to higher level by grouping by the entire row set
 */
final class SourceIdRangeDeterminer extends AbstractQuery
{
    private readonly PartitionValueResolver $valueResolver;

    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $summaryMetadata,
    ) {
        $summaryClass = $summaryMetadata->getSummaryClass();

        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->from($class, 'root');

        if (is_a($summaryClass, HasQueryBuilderModifier::class, true)) {
            $summaryClass::modifyQueryBuilder($queryBuilder);
        }

        parent::__construct($queryBuilder);

        $this->valueResolver = $this->summaryMetadata
            ->getPartition()
            ->getSource()[$class]
            ?? throw new MetadataException(\sprintf(
                'Value resolver for class "%s" not found',
                $class,
            ));
    }

    public function getMinId(): int|string|null
    {
        $partitionProperty = $this->valueResolver
            ->getInvolvedProperties()[0];

        $field = \sprintf(
            'root.%s',
            $partitionProperty,
        );

        $result = $this->getQueryBuilder()
            ->select($field)
            ->orderBy($field, 'ASC')
            ->setMaxResults(1)
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
        $partitionProperty = $this->valueResolver
            ->getInvolvedProperties()[0];

        $field = \sprintf(
            'root.%s',
            $partitionProperty,
        );

        $result = $this->getQueryBuilder()
            ->select($field)
            ->orderBy($field, 'DESC')
            ->setMaxResults(1)
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
}
