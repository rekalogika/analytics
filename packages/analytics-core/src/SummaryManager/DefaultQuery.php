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
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\SummaryManager\Query\SummarizerQuery;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultResult;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class DefaultQuery implements Query
{
    /**
     * The class of the summary table.
     *
     * @var null|class-string
     */
    private ?string $from = null;

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
     * @var list<string>|null
     */
    private ?array $dimensionChoices = null;

    /**
     * @var list<string>|null
     */
    private ?array $measureChoices = null;

    private ?SummaryMetadata $summaryMetadata = null;

    private ?EntityManagerInterface $entityManager = null;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly int $queryResultLimit,
        private readonly int $fillingNodesLimit,
    ) {}

    #[\Override]
    public function getResult(): Result
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $summarizerQuery = new SummarizerQuery(
            entityManager: $this->getEntityManager(),
            query: $this,
            metadata: $this->getSummaryMetadata(),
            queryResultLimit: $this->queryResultLimit,
        );

        return $this->result = new DefaultResult(
            label: $this->getSummaryMetadata()->getLabel(),
            summaryClass: $this->getSummaryMetadata()->getSummaryClass(),
            query: $this,
            metadata: $this->getSummaryMetadata(),
            summarizerQuery: $summarizerQuery,
            propertyAccessor: $this->propertyAccessor,
            entityManager: $this->getEntityManager(),
            fillingNodesLimit: $this->fillingNodesLimit,
        );
    }

    //
    // metadata
    //

    private function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata
            ??= $this->summaryMetadataFactory
            ->getSummaryMetadata($this->getFrom());
    }

    //
    // doctrine
    //

    private function getEntityManager(): EntityManagerInterface
    {
        if ($this->entityManager === null) {
            $entityManager = $this->managerRegistry->getManagerForClass($this->getFrom());

            if (!$entityManager instanceof EntityManagerInterface) {
                throw new InvalidArgumentException(\sprintf(
                    'The class "%s" is not managed by Doctrine ORM.',
                    $this->getFrom(),
                ));
            }

            $this->entityManager = $entityManager;
        }

        return $this->entityManager;
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
            'dimension' => $this->getDimensionChoices(),
            'measure' => $this->getMeasureChoices(),
            'both' => array_merge($this->getDimensionChoices(), $this->getMeasureChoices()),
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
    // choices
    //

    /**
     * @return list<string>
     */
    private function getDimensionChoices(): array
    {
        return $this->dimensionChoices
            ??= array_merge(
                array_keys($this->getSummaryMetadata()->getLeafDimensions()),
                ['@values'],
            );
    }

    /**
     * @return list<string>
     */
    private function getMeasureChoices(): array
    {
        return $this->measureChoices
            ??= array_keys($this->getSummaryMetadata()->getMeasures());
    }

    //
    // from
    //

    /**
     * @return class-string
     */
    #[\Override]
    public function getFrom(): string
    {
        if ($this->from === null) {
            throw new InvalidArgumentException('Query must be initialized with from() method.');
        }

        return $this->from;
    }

    /**
     * @param class-string $class
     */
    #[\Override]
    public function from(string $class): static
    {
        $this->result = null;
        $this->from = $class;

        return $this;
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
