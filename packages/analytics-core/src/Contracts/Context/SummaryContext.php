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

use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class SummaryContext
{
    public function __construct(
        private SummaryMetadata $summaryMetadata,
    ) {}

    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata;
    }

    /**
     * @template T of object
     * @param null|class-string<T> $class
     * @return ($class is null ? mixed : T|null)
     */
    public function getUserValue(
        string $property,
        mixed $rawValue,
        ?string $class = null,
    ): mixed {
        $propertyMetadata = $this->summaryMetadata
            ->getProperty($property);

        if (!$propertyMetadata instanceof DimensionMetadata) {
            throw new InvalidArgumentException(\sprintf(
                'Getting user value is not supported, the property "%s" is not a dimension property.',
                $property,
            ));
        }

        $valueResolver = $propertyMetadata->getValueResolver();

        if (!$valueResolver instanceof UserValueTransformer) {
            throw new InvalidArgumentException(\sprintf(
                'Getting user value is not supported, but the value resolver of property "%s" is "%s" which is not an instance of "%s".',
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
