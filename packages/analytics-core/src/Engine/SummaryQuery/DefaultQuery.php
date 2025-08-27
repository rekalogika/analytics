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

namespace Rekalogika\Analytics\Engine\SummaryQuery;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultResult;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
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
     * @var list<Expression>
     */
    private array $dice = [];

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

        return $this->result = new DefaultResult(
            label: $this->getSummaryMetadata()->getLabel(),
            summaryClass: $this->getSummaryMetadata()->getSummaryClass(),
            query: $this,
            metadata: $this->getSummaryMetadata(),
            propertyAccessor: $this->propertyAccessor,
            entityManager: $this->getEntityManager(),
            nodesLimit: $this->fillingNodesLimit,
            queryResultLimit: $this->queryResultLimit,
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
    public function getDimensions(): array
    {
        $dimensions = $this->dimensions;

        if (!\in_array('@values', $dimensions, true)) {
            $dimensions[] = '@values';
        }

        return $dimensions;
    }

    #[\Override]
    public function setDimensions(string ...$dimensions): static
    {
        $this->result = null;

        $dimensions = array_values(array_unique($dimensions));
        $this->ensureFieldValid($dimensions, 'dimension');

        $this->dimensions = $dimensions;

        return $this;
    }

    #[\Override]
    public function addDimension(string ...$dimensions): static
    {
        $this->setDimensions(...array_merge($this->dimensions, $dimensions));

        return $this;
    }

    //
    // filters
    //

    #[\Override]
    public function getDice(): ?Expression
    {
        if ($this->dice === []) {
            return null;
        }

        return Criteria::expr()->andX(...$this->dice);
    }

    #[\Override]
    public function dice(?Expression $predicate): static
    {
        $this->dice = [];

        if ($predicate !== null) {
            $this->andDice($predicate);
        }

        return $this;
    }

    #[\Override]
    public function andDice(Expression $predicate): static
    {
        $this->result = null;
        $this->dice[] = $predicate;

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
