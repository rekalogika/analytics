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
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContextFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final class DefaultQuery implements Query
{
    /**
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

    private ?CubeCell $result = null;

    /**
     * @param SummaryMetadataFactory $summaryMetadataFactory
     * @param ResultContextFactory $resultContextFactory
     */
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private ResultContextFactory $resultContextFactory,
    ) {}

    #[\Override]
    public function getResult(): CubeCell
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $context = $this->resultContextFactory->createResultContext(clone $this);

        return $this->result = $context->getCellRepository()->getApexCell();
    }

    //
    // metadata
    //

    private function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadataFactory
            ->getSummaryMetadata($this->getFrom());
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
        if ($type === 'dimension') {
            $this->getSummaryMetadata()->ensureDimensionsValid($fields);
            return;
        }

        if ($type === 'measure') {
            $this->getSummaryMetadata()->ensureMeasuresValid($fields);
            return;
        }

        $this->getSummaryMetadata()->ensurePropertiesValid($fields);
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
    public function withDimensions(string ...$dimensions): static
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
        $this->withDimensions(...array_merge($this->dimensions, $dimensions));

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
