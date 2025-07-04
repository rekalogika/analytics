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

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Common\Util\HierarchicalTranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HierarchyAwareLabel implements TranslatableInterface
{
    /**
     * @var null|list<TranslatableInterface>
     */
    private ?array $labels = null;

    public function __construct(private readonly DimensionMetadata $dimension) {}

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->getLeaf()->trans($translator, $locale);
    }

    /**
     * @return list<TranslatableInterface>
     */
    private function getLabels(): array
    {
        if ($this->labels === null) {
            $this->labels = [];
            $current = $this->dimension;

            while ($current !== null) {
                $this->labels[] = $current->getRawLabel();
                $current = $current->getParent();
            }

            // Reverse the order to have root first
            $this->labels = array_reverse($this->labels);
        }

        return $this->labels;
    }

    public function getLeaf(): TranslatableInterface
    {
        $labels = $this->getLabels();

        /** @psalm-suppress MixedAssignment */
        $result = end($labels);

        if (!$result instanceof TranslatableInterface) {
            throw new LogicException('DimensionLabel has no labels.');
        }

        return $result;
    }

    public function getRoot(): TranslatableInterface
    {
        $labels = $this->getLabels();

        if (\count($labels) === 0) {
            throw new LogicException('DimensionLabel has no labels.');
        }

        /** @psalm-suppress MixedAssignment */
        $result = reset($labels);

        if (!$result instanceof TranslatableInterface) {
            throw new LogicException('DimensionLabel has no labels.');
        }

        return $result;
    }

    public function getRootToLeaf(): TranslatableInterface
    {
        return new HierarchicalTranslatableMessage($this->getLabels());
    }

    public function getRootAndLeaf(): TranslatableInterface
    {
        return new HierarchicalTranslatableMessage([
            $this->getRoot(),
            $this->getLeaf(),
        ]);
    }

    public function getRootToParent(): ?TranslatableInterface
    {
        $labels = $this->getLabels();

        if (\count($labels) < 2) {
            throw new LogicException('DimensionLabel has no parent.');
        }

        /** @psalm-suppress MixedAssignment */
        $result = \array_slice($labels, 0, -1);

        if (\count($result) === 0) {
            return null;
        }

        return new HierarchicalTranslatableMessage($result);
    }

    public function getRootChildToParent(): ?TranslatableInterface
    {
        $labels = $this->getLabels();

        if (\count($labels) < 2) {
            throw new LogicException('DimensionLabel has no parent.');
        }

        /** @psalm-suppress MixedAssignment */
        $result = \array_slice($labels, 1, -1);

        if (\count($result) === 0) {
            return null;
        }

        return new HierarchicalTranslatableMessage($result);
    }
}
