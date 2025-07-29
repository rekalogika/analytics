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

use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract readonly class PropertyMetadata implements TranslatableInterface
{
    /**
     * @param class-string|null $typeClass
     * @param list<string> $involvedSourceProperties
     */
    protected function __construct(
        private string $name,
        private string $propertyName,
        private TranslatableInterface $label,
        private ?string $typeClass,
        private bool $hidden,
        private AttributeCollection $attributes,
        private array $involvedSourceProperties,
        private ?SummaryMetadata $summaryMetadata = null,
    ) {}

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->label->trans($translator, $locale);
    }

    /**
     * Fully qualified property name. e.g. `time.hour`
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Property name without parent name. e.g. `hour`
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        if ($this->summaryMetadata === null) {
            throw new MetadataException('Summary table metadata is not set');
        }

        return $this->summaryMetadata;
    }

    public function getAttributes(): AttributeCollection
    {
        return $this->attributes;
    }

    /**
     * @return class-string|null
     */
    public function getTypeClass(): ?string
    {
        return $this->typeClass;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @return list<string>
     */
    public function getInvolvedSourceProperties(): array
    {
        return $this->involvedSourceProperties;
    }
}
