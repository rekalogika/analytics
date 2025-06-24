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

namespace Rekalogika\Analytics\Bundle\UI\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\UI\Filter;
use Rekalogika\Analytics\Bundle\UI\Filter\Model\Choice;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Contracts\Translation\TranslatableInterface;

final class EqualFilter implements Filter
{
    /**
     * @var list<mixed>|null
     */
    private ?array $values = null;

    /**
     * @var list<Choice>|null
     */
    private ?array $choices = null;

    private ?TranslatableInterface $label = null;

    /**
     * @param class-string $class
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly string $class,
        private readonly Stringifier $stringifier,
        private readonly DistinctValuesResolver $distinctValuesResolver,
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly string $dimension,
        private readonly array $inputArray,
    ) {}

    #[\Override]
    public function getTemplate(): string
    {
        return '@RekalogikaAnalytics/filter/equal_filter.html.twig';
    }

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension;
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label ??= $this->summaryMetadataFactory
            ->getSummaryMetadata($this->class)
            ->getDimension($this->dimension)
            ->getLabel();
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
                throw new InvalidArgumentException('Invalid input value');
            }

            $values[] = $this->distinctValuesResolver->getValueFromId(
                class: $this->class,
                dimension: $this->dimension,
                id: $v,
            );
        }

        return $this->values = $values;
    }

    #[\Override]
    public function createExpression(): ?Expression
    {
        if ($this->getValues() === []) {
            return null;
        }

        return Criteria::expr()->in(
            $this->dimension,
            $this->getValues(),
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

        $choices = $this->distinctValuesResolver->getDistinctValues(
            class: $this->class,
            dimension: $this->dimension,
            limit: 100,
        ) ?? [];

        $choices2 = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($choices as $id => $value) {
            if ($id === Choice::NULL) {
                throw new InvalidArgumentException('ID cannot be the same as NULL value');
            }

            $choices2[] = new Choice(
                id: $id,
                value: $value,
                label: $this->stringifier->toString($value),
                selected: \in_array(
                    $value,
                    $this->getValues(),
                    strict: true,
                ),
            );
        }

        $nullLabel = $this->summaryMetadataFactory
            ->getSummaryMetadata($this->class)
            ->getDimension($this->dimension)
            ->getNullLabel();

        $choices2[] = new Choice(
            id: Choice::NULL,
            value: null,
            label: $this->stringifier->toString($nullLabel),
            selected: \in_array(
                null,
                $this->getValues(),
                strict: true,
            ),
        );

        return $this->choices = $choices2;
    }
}
