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

namespace Rekalogika\Analytics\UX\PanelBundle\Internal;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements \IteratorAggregate<PivotTableItemOption>
 */
final class PivotTableItemOptionGroup implements
    \IteratorAggregate,
    TranslatableInterface
{
    /**
     * @var list<PivotTableItemOption> $options
     */
    private array $options = [];

    public static function create(DimensionMetadata $dimension): ?self
    {
        if (\count($dimension->getChildren()) > 0) {
            throw new InvalidArgumentException(
                'PivotTableItemOptionGroup must be created with a leaf dimension.',
            );
        }

        $label = $dimension->getLabel()->getRootChildToParent();

        if ($label === null) {
            return null; // No label available, cannot create group
        }

        $name = $dimension->getName();
        $propertyName = $dimension->getPropertyName();

        // remove from $name the last part containing $propertyName
        if (str_ends_with($name, $propertyName)) {
            $name = substr($name, 0, -\strlen($propertyName) - 1);
        }

        return new self(
            key: $name,
            label: $label,
        );
    }

    private function __construct(
        private readonly string $key,
        private readonly TranslatableInterface $label,
    ) {}

    public function getTagName(): string
    {
        return 'optgroup';
    }

    public function getKey(): string
    {
        return $this->key;
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->label->trans($translator, $locale);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->options;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    public function addOption(PivotTableItemOption $option): void
    {
        if ($option->getOptGroupId() !== $this->key) {
            throw new InvalidArgumentException(
                'PivotTableItemOption must have the same opt group ID as this group.',
            );
        }

        $this->options[] = $option;
    }
}
