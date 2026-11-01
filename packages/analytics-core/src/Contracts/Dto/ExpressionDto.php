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

namespace Rekalogika\Analytics\Contracts\Dto;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

/**
 * Base class of the serializable representations of a Doctrine Collections
 * `Expression`, used to carry a dice predicate inside a `CoordinatesDto`.
 *
 * Each subclass corresponds to one kind of expression, and converts to and
 * from a plain array having a `class` key that identifies the kind. The array
 * form can be JSON-encoded, e.g. to be put in a URL, and converted back using
 * `fromArray()`.
 *
 * @see CoordinatesDto
 * @see \Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper
 */
abstract readonly class ExpressionDto implements \JsonSerializable
{
    /**
     * Converts the expression to its array form.
     *
     * @return array<string,mixed>
     */
    abstract public function toArray(): array;

    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Converts the array form of any kind of expression back into the matching
     * subclass, based on the `class` key.
     *
     * @param array<string,mixed> $array
     * @throws InvalidArgumentException If the array is not a valid expression.
     */
    public static function fromArray(array $array): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!isset($array['class']) || !\is_string($array['class'])) {
            throw new InvalidArgumentException('Invalid array representation for ExpressionDto.');
        }

        switch ($array['class']) {
            case 'comparison':
                return ComparisonDto::fromArray($array);
            case 'compositeExpression':
                return CompositeExpressionDto::fromArray($array);
            case 'value':
                return ValueDto::fromArray($array);
            default:
                throw new InvalidArgumentException('Unsupported expression class: ' . $array['class']);
        }
    }
}
