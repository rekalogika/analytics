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
use Rekalogika\Analytics\DistinctValuesResolver;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Query\Result;
use Rekalogika\Analytics\SummaryManager\Query\SummarizerQuery;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

final class SummaryQuery
{
    private mixed $lowerBound = null;

    private mixed $upperBound = null;

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

    /**
     * @param non-empty-array<string,Field> $dimensionChoices
     * @param array<string,Field> $measureChoices
     * @param non-empty-array<string,string|TranslatableInterface|\Stringable|iterable<string,TranslatableInterface>> $hierarchicalDimensionChoices
     */
    public function __construct(
        private readonly array $dimensionChoices,
        private readonly array $hierarchicalDimensionChoices,
        private readonly array $measureChoices,
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $metadata,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly DistinctValuesResolver $distinctValuesResolver,
    ) {}

    public function getResult(): Result
    {
        $summarizer = new SummarizerQuery(
            queryBuilder: $this->entityManager->createQueryBuilder(),
            query: $this,
            metadata: $this->metadata,
            propertyAccessor: $this->propertyAccessor,
        );

        return $summarizer->execute();
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->metadata->getSummaryClass();
    }

    //
    // available choices
    //

    /**
     * @return array<string,Field>
     */
    public function getDimensionChoices(): array
    {
        return $this->dimensionChoices;
    }

    /**
     * @return array<string,string|TranslatableInterface|\Stringable|iterable<string,string|TranslatableInterface>>
     * @todo deprecate
     * @internal
     */
    public function getHierarchicalDimensionChoices(): array
    {
        return $this->hierarchicalDimensionChoices;
    }

    /**
     * @return array<string,Field>
     */
    public function getMeasureChoices(): array
    {
        return $this->measureChoices;
    }

    //
    // distinct values resolver
    //

    /**
     * @param class-string $class
     * @return null|iterable<string,mixed>
     */
    public function getDistinctValues(
        string $class,
        string $dimension
    ): ?iterable {
        return $this->distinctValuesResolver->getDistinctValues(
            class: $class,
            dimension: $dimension,
            limit: 100,
        );
    }

    /**
     * @param class-string $class
     */
    public function getValueFromId(
        string $class,
        string $dimension,
        string $id
    ): mixed {
        return $this->distinctValuesResolver->getValueFromId(
            class: $class,
            dimension: $dimension,
            id: $id,
        );
    }

    //
    // bounds
    //

    public function getLowerBound(): mixed
    {
        return $this->lowerBound;
    }

    public function setLowerBound(mixed $lowerBound): void
    {
        $this->lowerBound = $lowerBound;
    }

    public function getUpperBound(): mixed
    {
        return $this->upperBound;
    }

    public function setUpperBound(mixed $upperBound): void
    {
        $this->upperBound = $upperBound;
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
            if (!\array_key_exists($field, $type)) {
                $invalid[] = $field;
            }
        }

        if ($invalid !== []) {
            throw new \InvalidArgumentException(\sprintf('Invalid field: %s', implode(', ', $invalid)));
        }
    }

    //
    // dimensions
    //

    /**
     * @return list<string>
     */
    public function getGroupBy(): array
    {
        return $this->dimensions;
    }

    public function groupBy(string ...$dimensions): self
    {
        $dimensions = array_values(array_unique($dimensions));
        $this->ensureFieldValid($dimensions, 'dimension');

        $this->dimensions = $dimensions;

        return $this;
    }

    public function addGroupBy(string ...$dimensions): self
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
    public function getSelect(): array
    {
        return $this->measures;
    }

    public function select(string ...$measures): self
    {
        $measures = array_values(array_unique($measures));
        $this->ensureFieldValid($measures, 'measure');

        $this->measures = $measures;

        return $this;
    }

    public function addSelect(string ...$measures): self
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
    public function getWhere(): array
    {
        return $this->where;
    }

    public function where(Expression $expression): self
    {
        $this->where = [];
        $this->andWhere($expression);

        return $this;
    }

    public function andWhere(Expression $expression): self
    {
        $this->where[] = $expression;

        return $this;
    }

    //
    // order
    //

    /**
     * @return array<string,Order>
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function orderBy(string $field, Order $direction = Order::Ascending): self
    {
        $this->orderBy = [];
        $this->addOrderBy($field, $direction);

        return $this;
    }

    public function addOrderBy(string $field, Order $direction = Order::Ascending): self
    {
        $this->ensureFieldValid([$field], 'both');

        if ($field === '@values') {
            throw new \InvalidArgumentException('Cannot order by @values');
        }

        $this->orderBy[$field] = $direction;

        return $this;
    }
}
