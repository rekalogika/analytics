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
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class MeasureMetadata extends PropertyMetadata implements HasInvolvedProperties
{
    /**
     * @var list<string>
     */
    private array $involvedProperties;

    /**
     * @param class-string $typeClass
     */
    public function __construct(
        private AggregateFunction $function,
        string $summaryProperty,
        TranslatableInterface $label,
        ?string $typeClass,
        private null|TranslatableInterface $unit,
        private ?string $unitSignature,
        private bool $virtual,
        bool $hidden,
        ?SummaryMetadata $summaryMetadata = null,
    ) {
        parent::__construct(
            summaryProperty: $summaryProperty,
            label: $label,
            typeClass: $typeClass,
            hidden: $hidden,
            summaryMetadata: $summaryMetadata,
        );

        // involved properties

        if ($function instanceof SummarizableAggregateFunction) {
            $this->involvedProperties = $function->getInvolvedProperties();
        } else {
            $this->involvedProperties = [];
        }
    }

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            function: $this->function,
            summaryProperty: $this->getSummaryProperty(),
            label: $this->getLabel(),
            typeClass: $this->getTypeClass(),
            unit: $this->unit,
            unitSignature: $this->unitSignature,
            virtual: $this->virtual,
            hidden: $this->isHidden(),
            summaryMetadata: $summaryMetadata,
        );
    }

    public function getFunction(): AggregateFunction
    {
        return $this->function;
    }

    public function getUnit(): null|TranslatableInterface
    {
        return $this->unit;
    }

    public function getUnitSignature(): ?string
    {
        return $this->unitSignature;
    }

    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    /**
     * @return list<string>
     */
    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->involvedProperties;
    }
}
