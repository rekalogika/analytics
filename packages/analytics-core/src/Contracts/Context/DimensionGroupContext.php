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

namespace Rekalogika\Analytics\Contracts\Context;

use Rekalogika\Analytics\Contracts\DimensionGroup\ContextAwareDimensionGroup;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * Context given to a `ContextAwareDimensionGroup` after the framework has
 * populated it from a row of the query result. It gives the getters of the
 * dimension group access to the metadata, and lets them turn the raw values
 * stored in the group's properties into user-facing values, e.g. an integer
 * stored in the database into a `TimeBin` object.
 *
 * @see ContextAwareDimensionGroup
 */
final readonly class DimensionGroupContext
{
    /**
     * @param DimensionMetadata $dimensionMetadata The metadata of the
     * dimension holding the dimension group.
     * @param ContextAwareDimensionGroup $rawObject The dimension group
     * object, holding the raw values as returned by Doctrine.
     */
    public function __construct(
        private DimensionMetadata $dimensionMetadata,
        private ContextAwareDimensionGroup $rawObject,
    ) {}

    /**
     * The metadata of the summary class the dimension group belongs to.
     */
    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->dimensionMetadata->getSummaryMetadata();
    }

    /**
     * The metadata of the dimension (in the summary class, or in the parent
     * dimension group) that holds this dimension group.
     */
    public function getDimensionMetadata(): DimensionMetadata
    {
        return $this->dimensionMetadata;
    }

    /**
     * Returns the user-facing value of one of the properties of the dimension
     * group. The raw value is read from the property, and transformed using
     * the `UserValueTransformer` of the dimension, which is the dimension's
     * value resolver.
     *
     * Call this from the getter of the property, so the getter returns the
     * user-facing value instead of the raw value stored in the database.
     *
     * @template T of object
     * @param string $property The name of the property inside the dimension
     * group.
     * @param null|class-string<T> $class If given, the result is checked to be
     * an instance of this class, and the return type is narrowed accordingly.
     * @return ($class is null ? mixed : T|null)
     * @throws InvalidArgumentException If the property is not a dimension, if
     * its value resolver is not a `UserValueTransformer`, or if the result is
     * not an instance of `$class`.
     */
    public function getUserValue(
        string $property,
        ?string $class = null,
    ): mixed {
        $reflectionClass = new \ReflectionClass($this->rawObject);
        $reflectionProperty = $reflectionClass->getProperty($property);

        /** @psalm-suppress MixedAssignment */
        $rawValue = $reflectionProperty->getValue($this->rawObject);

        $fullyQualifiedProperty = \sprintf(
            '%s.%s',
            $this->dimensionMetadata->getName(),
            $property,
        );

        $propertyMetadata = $this
            ->getSummaryMetadata()
            ->getProperty($fullyQualifiedProperty);

        if (!$propertyMetadata instanceof DimensionMetadata) {
            throw new InvalidArgumentException(\sprintf(
                'Getting user value is not supported, the property "%s" is not a dimension.',
                $property,
            ));
        }

        $valueResolver = $propertyMetadata->getValueResolver();

        if (!$valueResolver instanceof UserValueTransformer) {
            throw new InvalidArgumentException(\sprintf(
                'Getting user value is not supported. The value resolver of property "%s" is "%s" which is not an instance of "%s".',
                $property,
                $valueResolver::class,
                UserValueTransformer::class,
            ));
        }

        $valueTransformerContext = new ValueTransformerContext($propertyMetadata);
        /** @psalm-suppress MixedAssignment */
        $result = $valueResolver->getUserValue($rawValue, $valueTransformerContext);

        if (
            $class !== null
            && $result !== null
            && (
                !\is_object($result)
                || !is_a($result, $class, true)
            )
        ) {
            throw new InvalidArgumentException(\sprintf(
                'The user value for property "%s" is not an instance of "%s", but "%s".',
                $property,
                $class,
                get_debug_type($result),
            ));
        }

        /** @psalm-suppress MixedReturnStatement */
        return $result;
    }
}
