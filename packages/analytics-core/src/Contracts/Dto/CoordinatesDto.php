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
 * Serializable representation of a `Coordinates`: the members of each
 * dimension, plus the dice predicate in effect. Use `CoordinatesMapper` to
 * convert between the two.
 *
 * The DTO converts to and from a plain array, and can be JSON-encoded. This
 * allows a cell to be identified outside of a query result, e.g. in a URL that
 * links from a cell in a chart to a page listing its source entities.
 *
 * @see \Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper
 */
final readonly class CoordinatesDto implements \JsonSerializable
{
    /**
     * @param array<string,int|string|null> $members Key is dimension name,
     * value is the serialized raw member value, as produced by the
     * `ValueSerializer` of the dimension.
     * @param ExpressionDto|null $predicate The dice predicate in effect when
     * the cell was produced, or null if none.
     */
    public function __construct(
        private array $members,
        private ?ExpressionDto $predicate,
    ) {}

    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Converts the coordinates to their array form.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => 'coordinates',
            'members' => $this->members,
            'predicate' => $this->predicate?->toArray(),
        ];
    }

    /**
     * Converts the array form, as produced by `toArray()`, back into a DTO.
     *
     * @param array<string,mixed> $array
     * @throws InvalidArgumentException If the array is not valid coordinates.
     */
    public static function fromArray(array $array): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (
            !isset($array['class'], $array['members'])
            || $array['class'] !== 'coordinates'
            || !\is_array($array['members'])
        ) {
            throw new InvalidArgumentException('Invalid array representation for CoordinatesDto.');
        }

        $members = [];

        foreach ($array['members'] as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidArgumentException('Coordinates key must be a string.');
            }

            if (
                !\is_string($value)
                && !\is_int($value)
                && null !== $value
            ) {
                throw new InvalidArgumentException('Coordinates member value must be a string, an integer, or null.');
            }

            $members[$key] = $value;
        }

        $predicate = null;

        if (
            isset($array['predicate'])
            && \is_array($array['predicate'])
        ) {
            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             * @phpstan-ignore argument.type
             */
            $predicate = ExpressionDto::fromArray($array['predicate']);
        }

        return new self(
            members: $members,
            predicate: $predicate,
        );
    }

    /**
     * The serialized members, keyed by dimension name.
     *
     * @return array<string,int|string|null>
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * The dice predicate, or null if none.
     */
    public function getPredicate(): ?ExpressionDto
    {
        return $this->predicate;
    }
}
