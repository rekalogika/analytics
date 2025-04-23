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

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Result\Query;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\SummaryManager\Query\SummarizerQuery;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultResult;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class DefaultQuery implements Query
{
    /**
     * @var list<string>
     */
    private array $dimensions = [];

    /**
     * @var list<string>
     */
    private array $measures = [];

    /**
     * @var list<Expression>
     */
    private array $where = [];

    /**
     * @var array<string,Order>
     */
    private array $orderBy = [];

    private ?Result $result = null;

    /**
     * @param non-empty-list<string> $dimensionChoices
     * @param list<string> $measureChoices
     */
    public function __construct(
        private readonly array $dimensionChoices,
        private readonly array $measureChoices,
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $metadata,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly int $queryResultLimit,
        private readonly int $fillingNodesLimit,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->metadata->getSummaryClass();
    }

    #[\Override]
    public function getResult(): Result
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $summarizerQuery = new SummarizerQuery(
            queryBuilder: $this->entityManager->createQueryBuilder(),
            query: $this,
            metadata: $this->metadata,
            queryResultLimit: $this->queryResultLimit,
        );

        return $this->result = new DefaultResult(
            label: $this->metadata->getLabel(),
            summaryClass: $this->metadata->getSummaryClass(),
            query: $this,
            metadata: $this->metadata,
            summarizerQuery: $summarizerQuery,
            propertyAccessor: $this->propertyAccessor,
            entityManager: $this->entityManager,
            fillingNodesLimit: $this->fillingNodesLimit,
        );
    }

    //
    // helpers
    //

    /**
     * @param list<string> $fields
     * @param 'dimension'|'measure'|'both' $type
     */
    private function ensureFieldValid(array $fields, string $type): void
    {
        $invalid = [];
        $type = match ($type) {
            'dimension' => $this->dimensionChoices,
            'measure' => $this->measureChoices,
            'both' => array_merge($this->dimensionChoices, $this->measureChoices),
        };

        foreach ($fields as $field) {
            if (!\in_array($field, $type, true)) {
                $invalid[] = $field;
            }
        }

        if ($invalid !== []) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid field: %s',
                implode(', ', $invalid),
            ));
        }
    }

    //
    // dimensions
    //

    /**
     * @return list<string>
     */
    #[\Override]
    public function getGroupBy(): array
    {
        return $this->dimensions;
    }

    #[\Override]
    public function groupBy(string ...$dimensions): static
    {
        $this->result = null;

        $dimensions = array_values(array_unique($dimensions));
        $this->ensureFieldValid($dimensions, 'dimension');

        $this->dimensions = $dimensions;

        return $this;
    }

    #[\Override]
    public function addGroupBy(string ...$dimensions): static
    {
        $this->groupBy(...array_merge($this->dimensions, $dimensions));

        return $this;
    }

    //
    // measures
    //

    /**
     * @return list<string>
     */
    #[\Override]
    public function getSelect(): array
    {
        return $this->measures;
    }

    #[\Override]
    public function select(string ...$measures): static
    {
        $this->result = null;

        $measures = array_values(array_unique($measures));
        $this->ensureFieldValid($measures, 'measure');

        $this->measures = $measures;

        return $this;
    }

    #[\Override]
    public function addSelect(string ...$measures): static
    {
        $this->select(...array_merge($this->measures, $measures));

        return $this;
    }

    //
    // filters
    //

    /**
     * @return list<Expression>
     */
    #[\Override]
    public function getWhere(): array
    {
        return $this->where;
    }

    #[\Override]
    public function where(Expression $expression): static
    {
        $this->where = [];
        $this->andWhere($expression);

        return $this;
    }

    #[\Override]
    public function andWhere(Expression $expression): static
    {
        $this->result = null;
        $this->where[] = $expression;

        return $this;
    }

    //
    // order
    //

    /**
     * @return array<string,Order>
     */
    #[\Override]
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    #[\Override]
    public function orderBy(string $field, Order $direction = Order::Ascending): static
    {
        $this->result = null;

        $this->orderBy = [];
        $this->addOrderBy($field, $direction);

        return $this;
    }

    #[\Override]
    public function addOrderBy(string $field, Order $direction = Order::Ascending): static
    {
        $this->ensureFieldValid([$field], 'both');

        if ($field === '@values') {
            throw new InvalidArgumentException('Ordering by @values is not supported.');
        }

        $this->result = null;
        $this->orderBy[$field] = $direction;

        return $this;
    }
}
