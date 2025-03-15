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

namespace Rekalogika\Analytics\Bundle\UI\Model;

use Doctrine\Common\Collections\Criteria;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

final class EqualFilter implements FilterExpression
{
    /**
     * @var list<mixed>|null
     */
    private ?array $values = null;

    /**
     * @var list<Choice>|null
     */
    private ?array $choices = null;

    /**
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly SummaryQuery $query,
        private readonly Stringifier $stringifier,
        private readonly string $dimension,
        private readonly array $inputArray,
    ) {}

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        $dimensionField = $this->query->getDimensionChoices()[$this->dimension]
            ?? throw new \InvalidArgumentException(\sprintf('Dimension "%s" not found', $this->dimension));

        return $dimensionField;
    }

    /**
     * @return list<mixed>
     */
    public function getValues(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        /** @psalm-suppress MixedAssignment */
        $inputValues = $this->inputArray['values'] ?? [];
        $values = [];

        if (!\is_array($inputValues)) {
            $inputValues = [];
        }

        /** @psalm-suppress MixedAssignment */
        foreach ($inputValues as $v) {
            if ($v === Choice::NULL) {
                $values[] = null;

                continue;
            }

            if (!\is_string($v)) {
                throw new \InvalidArgumentException('Invalid input value');
            }

            $values[] = $this->query->getValueFromId(
                class: $this->query->getClass(),
                dimension: $this->dimension,
                id: $v,
            );
        }

        return $this->values = $values;
    }

    #[\Override]
    public function applyToQuery(SummaryQuery $query): void
    {
        $query->andWhere(
            Criteria::expr()->in(
                $this->dimension,
                $this->getValues(),
            ),
        );
    }

    /**
     * @return list<Choice>
     */
    public function getChoices(): array
    {
        if ($this->choices !== null) {
            return $this->choices;
        }

        $choices = $this->query
            ->getDistinctValues($this->query->getClass(), $this->dimension);

        if ($choices === null) {
            return [];
        }

        $choices2 = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($choices as $id => $value) {
            if ($id === Choice::NULL) {
                throw new \InvalidArgumentException('ID cannot be the same as NULL value');
            }

            $choices2[] = new Choice(
                id: $id,
                value: $value,
                label: $this->stringifier->toString($value),
            );
        }

        $choices2[] = new Choice(
            id: Choice::NULL,
            value: null,
            label: $this->stringifier->toString(new TranslatableMessage('(None)')),
        );

        return $this->choices = $choices2;
    }
}
