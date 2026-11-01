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

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Metadata\Util\HierarchicalTranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The label of a dimension, with access to the labels of its ancestors. For a
 * dimension inside a dimension group, e.g. the year inside a time group, the
 * label alone ("Year") is often ambiguous, so the user interface can choose
 * between several combinations of the labels along the path from the root
 * dimension to this one. Translating the object directly gives the leaf
 * label.
 *
 * Given the path Time / Civil / Year, the methods return:
 *
 * - `getLeaf()`: Year
 * - `getRoot()`: Time
 * - `getRootToLeaf()`: Time / Civil / Year
 * - `getRootAndLeaf()`: Time / Year
 * - `getRootToParent()`: Time / Civil
 * - `getRootChildToParent()`: Civil
 */
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
     * The labels along the path, root first, leaf last.
     *
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

            // Reverse the order to have root first.
            $this->labels = array_reverse($this->labels);
        }

        return $this->labels;
    }

    /**
     * The label of the dimension itself.
     */
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

    /**
     * The label of the root dimension. Same as `getLeaf()` for a root
     * dimension.
     */
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

    /**
     * Every label along the path, joined with " / ".
     */
    public function getRootToLeaf(): TranslatableInterface
    {
        return new HierarchicalTranslatableMessage($this->getLabels());
    }

    /**
     * The root and the leaf labels joined with " / ", skipping the levels in
     * between. Just the label for a root dimension.
     */
    public function getRootAndLeaf(): TranslatableInterface
    {
        $root = $this->getRoot();
        $leaf = $this->getLeaf();

        if ($root === $leaf) {
            return $root;
        }

        return new HierarchicalTranslatableMessage([
            $this->getRoot(),
            $this->getLeaf(),
        ]);
    }

    /**
     * Every label along the path except the leaf, joined with " / ".
     *
     * @throws LogicException For a root dimension.
     */
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

    /**
     * The labels between the root and the leaf, both excluded, joined with
     * " / ". Null if the dimension is a direct child of the root.
     *
     * @throws LogicException For a root dimension.
     */
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
