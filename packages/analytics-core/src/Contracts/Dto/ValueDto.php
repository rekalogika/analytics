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
 * Serializable representation of a Doctrine Collections `Value`: the operand
 * of a comparison. The value is in its serialized form, as produced by the
 * `ValueSerializer` of the dimension, so an object member such as a related
 * entity is represented by its identifier.
 */
final readonly class ValueDto extends ExpressionDto
{
    /**
     * @param int|string|array<array-key,mixed> $value The serialized value.
     * An array is used for operators that take several values, e.g. `IN`.
     */
    public function __construct(
        public string|int|array|null $value,
    ) {}

    #[\Override]
    public function toArray(): array
    {
        return [
            'class' => 'value',
            'value' => $this->value,
        ];
    }

    #[\Override]
    public static function fromArray(array $array): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (
            !\array_key_exists('value', $array)
            || !\array_key_exists('class', $array)
            || $array['class'] !== 'value'
            || (
                !\is_string($array['value'])
                && !\is_int($array['value'])
                && !\is_array($array['value'])
                && $array['value'] !== null
            )
        ) {
            throw new InvalidArgumentException('Invalid array representation for ValueDto.');
        }

        return new self($array['value']);
    }
}
