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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * Context given to a `ContextAwareSummary` when the framework instantiates
 * the summary entity from a row of the query result. It gives the getters of
 * the summary access to the metadata, and lets them turn the raw values of the
 * row into user-facing values.
 *
 * @see ContextAwareSummary
 */
final readonly class SummaryContext
{
    /**
     * @param SummaryMetadata $summaryMetadata The metadata of the summary
     * class.
     * @param array<string,mixed> $rawInput The row of the query result, keyed
     * by property name, with the raw values as returned by Doctrine.
     */
    public function __construct(
        private SummaryMetadata $summaryMetadata,
        private array $rawInput,
    ) {}

    /**
     * The metadata of the summary class.
     */
    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata;
    }

    /**
     * Returns the user-facing value of a property of the summary. The raw
     * value is taken from the query result row, and transformed using the
     * `UserValueTransformer` of the property: the value resolver for a
     * dimension, or the aggregate function for a measure.
     *
     * Call this from the getter of the property, so the getter returns the
     * user-facing value instead of the raw value stored in the database.
     *
     * @template T of object
     * @param string $property The name of the property in the summary class.
     * @param null|class-string<T> $class If given, the result is checked to be
     * an instance of this class, and the return type is narrowed accordingly.
     * @return ($class is null ? mixed : T|null)
     * @throws InvalidArgumentException If the property is not in the result
     * row, if it is neither a dimension nor a measure, if its resolver or
     * function is not a `UserValueTransformer`, or if the result is not an
     * instance of `$class`.
     */
    public function getUserValue(
        string $property,
        ?string $class = null,
    ): mixed {
        if (!\array_key_exists($property, $this->rawInput)) {
            throw new InvalidArgumentException(\sprintf(
                'Expecting property "%s" to be present in the raw input, but it is not found.',
                $property,
            ));
        }

        /** @psalm-suppress MixedAssignment */
        $rawValue = $this->rawInput[$property];

        $propertyMetadata = $this->summaryMetadata
            ->getProperty($property);

        if ($propertyMetadata instanceof DimensionMetadata) {
            $transformer = $propertyMetadata->getValueResolver();
        } elseif ($propertyMetadata instanceof MeasureMetadata) {
            $transformer = $propertyMetadata->getFunction();
        } else {
            throw new InvalidArgumentException(\sprintf(
                'Getting user value is not supported, the property "%s" is not a dimension or measure.',
                $property,
            ));
        }

        if (!$transformer instanceof UserValueTransformer) {
            throw new InvalidArgumentException(\sprintf(
                'Getting user value is not supported. The value resolver or aggregate function of property "%s" is "%s" which is not an instance of "%s".',
                $property,
                $transformer::class,
                UserValueTransformer::class,
            ));
        }

        $valueTransformerContext = new ValueTransformerContext($propertyMetadata);
        /** @psalm-suppress MixedAssignment */
        $result = $transformer->getUserValue($rawValue, $valueTransformerContext);

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
