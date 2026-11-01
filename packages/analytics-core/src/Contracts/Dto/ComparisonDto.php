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
 * Serializable representation of a Doctrine Collections `Comparison`: a field
 * compared with a value using an operator, e.g. `country = 'FR'`.
 */
final readonly class ComparisonDto extends ExpressionDto
{
    /**
     * @param string $field The dimension name.
     * @param string $operator The comparison operator, as used by Doctrine
     * Collections, e.g. `=`, `<>`, `<`, `IN`.
     * @param ValueDto $value The value to compare the field with.
     */
    public function __construct(
        public string $field,
        public string $operator,
        public ValueDto $value,
    ) {}

    #[\Override]
    public function toArray(): array
    {
        return [
            'class' => 'comparison',
            'field' => $this->field,
            'op' => $this->operator,
            'value' => $this->value->toArray(),
        ];
    }

    #[\Override]
    public static function fromArray(array $array): ComparisonDto
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (
            !isset($array['class'], $array['field'], $array['op'], $array['value'])
            || $array['class'] !== 'comparison'
            || !\is_string($array['field'])
            || !\is_string($array['op'])
            || !\is_array($array['value'])
        ) {
            throw new InvalidArgumentException('Invalid array representation for ComparisonDto.');
        }

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @phpstan-ignore argument.type
         */
        $value = ValueDto::fromArray($array['value']);

        return new self(
            field: $array['field'],
            operator: $array['op'],
            value: $value,
        );
    }
}
