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

namespace Rekalogika\Analytics\Metadata\Source;

/**
 * A summary reads a property path of its source entity through a to-many
 * association, e.g. `customer.orders.time`. A change read through the
 * to-many association is not detected: the change of a collection is not
 * reported in the change set of its owner, and the source entities affected
 * by a change on the other side are ambiguous. It has to be reported with
 * `SummaryManager::markDirty()`.
 */
final readonly class ToManyPath
{
    /**
     * @param class-string $sourceClass The source class of the summary.
     * @param string $path The property path, e.g. `customer.orders.time`.
     * @param string $association The part of the path up to the to-many
     * association, e.g. `customer.orders`.
     * @param class-string $summaryClass The summary class.
     */
    public function __construct(
        private string $sourceClass,
        private string $path,
        private string $association,
        private string $summaryClass,
    ) {}

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getAssociation(): string
    {
        return $this->association;
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }
}
