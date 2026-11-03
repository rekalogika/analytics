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
 * A summary reads a property of an entity related to its source entity,
 * through one or more to-one associations. A change to the property affects
 * the source entities that refer to the related entity.
 *
 * For example, a summary of `Order` with the dimension source
 * `customer.country.region` depends on `country` of `Customer`, with the
 * reverse path `customer`, and on `region` of `Country`, with the reverse
 * path `customer.country`.
 *
 * A dependency without a property is on the existence of the related
 * entity: the source entities refer to it. Its deletion affects them, e.g.
 * the database sets their reference to null or deletes them. In the example,
 * there are such dependencies on `Customer`, `Country` and `Region`.
 */
final readonly class AssociationDependency
{
    /**
     * @param class-string $relatedClass The class of the related entity.
     * @param string|null $property The property of the related entity, as
     * reported in its change set, or null for a dependency on the existence
     * of the related entity.
     * @param class-string $sourceClass The source class of the summary.
     * @param string $reversePath The path from the source entity to the
     * related entity, e.g. `customer.country`. The source entities affected
     * by a change of a related entity are the ones whose path resolves to
     * the related entity.
     * @param class-string $summaryClass The summary class.
     */
    public function __construct(
        private string $relatedClass,
        private ?string $property,
        private string $sourceClass,
        private string $reversePath,
        private string $summaryClass,
    ) {}

    /**
     * @return class-string
     */
    public function getRelatedClass(): string
    {
        return $this->relatedClass;
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    public function getReversePath(): string
    {
        return $this->reversePath;
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    /**
     * Identifies the dependency, two dependencies with the same signature
     * are the same.
     */
    public function getSignature(): string
    {
        return \sprintf(
            '%s::%s<-%s::%s@%s',
            $this->relatedClass,
            $this->property ?? '',
            $this->sourceClass,
            $this->reversePath,
            $this->summaryClass,
        );
    }
}
