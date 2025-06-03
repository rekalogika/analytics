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

namespace Rekalogika\Analytics\Metadata\Summary;

use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class MeasureMetadata extends PropertyMetadata implements HasInvolvedProperties
{
    /**
     * @param non-empty-array<class-string,AggregateFunction> $function
     */
    public function __construct(
        private array $function,
        string $summaryProperty,
        TranslatableInterface $label,
        private null|TranslatableInterface $unit,
        private ?string $unitSignature,
        ?SummaryMetadata $summaryMetadata = null,
    ) {
        parent::__construct(
            summaryProperty: $summaryProperty,
            label: $label,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            function: $this->function,
            summaryProperty: $this->getSummaryProperty(),
            label: $this->getLabel(),
            unit: $this->unit,
            unitSignature: $this->unitSignature,
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * @return array<class-string,AggregateFunction>
     */
    public function getFunction(): array
    {
        return $this->function;
    }

    public function getFirstFunction(): AggregateFunction
    {
        $function = $this->function;

        return reset($function);
    }

    public function getUnit(): null|TranslatableInterface
    {
        return $this->unit;
    }

    public function getUnitSignature(): ?string
    {
        return $this->unitSignature;
    }

    /**
     * @return array<class-string,list<string>>
     */
    #[\Override]
    public function getInvolvedProperties(): array
    {
        $properties = [];

        foreach ($this->function as $class => $aggregateFunction) {
            foreach ($aggregateFunction->getInvolvedProperties() as $property) {
                $properties[$class][] = $property;
            }
        }

        $uniqueProperties = [];

        foreach ($properties as $class => $listOfProperties) {
            $uniqueProperties[$class] = array_values(array_unique($listOfProperties));
        }

        return $uniqueProperties;
    }
}
