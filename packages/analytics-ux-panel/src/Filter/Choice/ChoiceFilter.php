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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\Choice;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Symfony\Contracts\Translation\TranslatableInterface;

final class ChoiceFilter implements Filter
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
     * @param ChoiceFilterOptions<mixed> $options
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly ChoiceFilterOptions $options,
        private readonly DimensionMetadata $dimension,
        private readonly Stringifier $stringifier,
        private readonly Htmlifier $htmlifier,
        private readonly array $inputArray,
    ) {}

    #[\Override]
    public function getTemplate(): string
    {
        return '@RekalogikaAnalyticsUXPanel/filter/choice_filter.html.twig';
    }

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension->getName();
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->dimension->getLabel();
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

            // the input is user-controlled, ignore malformed values
            if (!\is_string($v)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $value = $this->options->getValueFromId($v);

            // An id that does not resolve, e.g. a deleted entity or one that
            // is no longer among the choices, must not become a filter for
            // null values. Null is only selected explicitly with Choice::NULL.
            if ($value === null) {
                continue;
            }

            $values[] = $value;
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
            $this->dimension->getName(),
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

        $choices = $this->options->getChoices();
        $choices2 = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($choices as $id => $value) {
            if ($id === Choice::NULL) {
                throw new InvalidArgumentException('ID cannot be the same as NULL value');
            }

            $choices2[] = new Choice(
                id: $id,
                value: $value,
                // An integer member is an identifier, like a year, not a
                // quantity. Do not let the number formatter add grouping
                // separators.
                label: \is_int($value) ? (string) $value : $this->stringifier->toString($value),
                htmlLabel: \is_int($value) ? (string) $value : $this->htmlifier->toHtml($value),
                selected: \in_array(
                    $value,
                    $this->getValues(),
                    strict: true,
                ),
            );
        }

        $nullLabel = $this->dimension->getNullLabel();

        $choices2[] = new Choice(
            id: Choice::NULL,
            value: null,
            label: $this->stringifier->toString($nullLabel),
            htmlLabel: $this->htmlifier->toHtml($nullLabel),
            selected: \in_array(
                null,
                $this->getValues(),
                strict: true,
            ),
        );

        return $this->choices = $choices2;
    }
}
