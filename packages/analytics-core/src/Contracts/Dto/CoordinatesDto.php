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

final readonly class CoordinatesDto
{
    /**
     * @param array<string,string|null> $members Key is dimension name, value is the
     * serialized raw member value.
     */
    public function __construct(
        private array $members,
        private ?ExpressionDto $predicate,
    ) {}

    /**
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
     * @param array<string,mixed> $array
     * @throws InvalidArgumentException
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

            if (!\is_string($value) && null !== $value) {
                throw new InvalidArgumentException('Coordinates member value must be a string or null.');
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
     * @return array<string,string|null>
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function getPredicate(): ?ExpressionDto
    {
        return $this->predicate;
    }
}
