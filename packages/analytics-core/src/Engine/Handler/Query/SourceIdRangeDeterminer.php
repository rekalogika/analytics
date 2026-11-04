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

namespace Rekalogika\Analytics\Engine\Handler\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\Infrastructure\SourceQueryBuilderFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final class SourceIdRangeDeterminer extends AbstractQuery
{
    /**
     * @var PartitionValueResolver<mixed>
     */
    private readonly PartitionValueResolver $valueResolver;

    public function __construct(
        EntityManagerInterface $entityManager,
        SummaryMetadata $summaryMetadata,
    ) {
        parent::__construct(SourceQueryBuilderFactory::create(
            entityManager: $entityManager,
            summaryMetadata: $summaryMetadata,
        ));

        $this->valueResolver = $summaryMetadata
            ->getPartition()
            ->getSource();
    }

    public function getMinKey(): int|string|null
    {
        $partitionProperty = $this->valueResolver
            ->getInvolvedProperties()[0];

        $field = \sprintf(
            'root.%s',
            $partitionProperty,
        );

        $query = $this->getSimpleQueryBuilder()
            ->select($field)
            ->orderBy($field, 'ASC')
            ->setMaxResults(1)
            ->getQuery();

        try {
            $result = $query->getSingleScalarResult();
        } catch (NoResultException) {
            $result = null;
        }

        if ($result === null || \is_int($result)) {
            return $result;
        }

        if (is_numeric($result)) {
            return (int) $result;
        }

        return (string) $result;
    }

    public function getMaxKey(): int|string|null
    {
        $partitionProperty = $this->valueResolver
            ->getInvolvedProperties()[0];

        $field = \sprintf(
            'root.%s',
            $partitionProperty,
        );

        $query = $this->getSimpleQueryBuilder()
            ->select($field)
            ->orderBy($field, 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        try {
            $result = $query->getSingleScalarResult();
        } catch (NoResultException) {
            $result = null;
        }

        if ($result === null || \is_int($result)) {
            return $result;
        }

        if (is_numeric($result)) {
            return (int) $result;
        }

        return (string) $result;
    }
}
