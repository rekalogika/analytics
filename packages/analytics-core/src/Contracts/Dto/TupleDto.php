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

final readonly class TupleDto
{
    /**
     * @param array<string,string> $members Key is dimension name, value is the
     * serialized raw member value.
     */
    public function __construct(
        private array $members,
        private ?ExpressionDto $condition,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => 'tuple',
            'members' => $this->members,
            'condition' => $this->condition?->toArray(),
        ];
    }

    /**
     * @param array<string,mixed> $array
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $array): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (
            !isset($array['class'], $array['members'])
            || $array['class'] !== 'tuple'
            || !\is_array($array['members'])
        ) {
            throw new InvalidArgumentException('Invalid array representation for TupleDto.');
        }

        $members = [];

        foreach ($array['members'] as $key => $value) {
            if (!\is_string($key) || !\is_string($value)) {
                throw new InvalidArgumentException('Tuple members must be key-value pairs of strings.');
            }

            $members[$key] = $value;
        }

        $condition = null;

        if (
            isset($array['condition'])
            && \is_array($array['condition'])
        ) {
            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             * @phpstan-ignore argument.type
             */
            $condition = ExpressionDto::fromArray($array['condition']);
        }

        return new self(
            members: $members,
            condition: $condition,
        );
    }

    /**
     * @return array<string,string>
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function getCondition(): ?ExpressionDto
    {
        return $this->condition;
    }
}
