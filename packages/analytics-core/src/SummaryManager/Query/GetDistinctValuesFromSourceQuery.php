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

use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Metadata\DimensionMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class GetDistinctValuesFromSourceQuery extends AbstractQuery
{
    public function __construct(
        DimensionMetadata $dimensionMetadata,
        private readonly QueryBuilder $queryBuilder,
        private readonly PropertyAccessorInterface $propertyAccessor,
        int $limit,
    ) {
        $summaryMetadata = $dimensionMetadata->getSummaryMetadata();
        $summaryClass = $summaryMetadata->getSummaryClass();
        $orderBy = $dimensionMetadata->getOrderBy();
        $dimension = $dimensionMetadata->getSummaryProperty();

        $entityManager = $this->queryBuilder->getEntityManager();
        $doctrineMetadata = $entityManager->getClassMetadata($summaryClass);

        // ensure the property is a relation
        if (!$doctrineMetadata->hasAssociation($dimension)) {
            throw new \InvalidArgumentException(\sprintf(
                'The property "%s" in class "%s" is not a relation',
                $dimension,
                $summaryClass,
            ));
        }

        // get relation class
        $relationClass = $doctrineMetadata->getAssociationTargetClass($dimension);

        $queryBuilder
            ->from($relationClass, 'root')
            ->select('root')
            ->setMaxResults($limit);

        parent::__construct($queryBuilder);

        if (\is_array($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                $dqlField = $this->getQueryContext()->resolvePath($field);

                $queryBuilder->addOrderBy($dqlField, $direction->value);
            }
        }
    }

    /**
     * @return array<string,object>
     */
    public function getResult(): array
    {
        /** @var list<object> */
        $result = $this->queryBuilder->getQuery()->getResult();

        $idField = $this->queryBuilder->getEntityManager()
            ->getClassMetadata($this->queryBuilder->getRootEntities()[0])
            ->getSingleIdentifierFieldName();

        $values = [];

        foreach ($result as $item) {
            $id = $this->propertyAccessor->getValue($item, $idField);

            if (!is_string($id) && !is_int($id)) {
                throw new \InvalidArgumentException(\sprintf(
                    'The identifier field "%s" in class "%s" is not a string or integer',
                    $idField,
                    $this->queryBuilder->getRootEntities()[0],
                ));
            }

            $id = (string) $id;

            $values[$id] = $item;
        }

        return $values;
    }
}
