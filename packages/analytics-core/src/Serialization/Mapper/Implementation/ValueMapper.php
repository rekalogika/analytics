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

namespace Rekalogika\Analytics\Serialization\Mapper\Implementation;

use Doctrine\Common\Collections\Expr\Value;
use Rekalogika\Analytics\Contracts\Dto\ValueDto;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Serialization\Mapper\Mapper;
use Rekalogika\Analytics\Serialization\Mapper\MapperContext;

/**
 * @implements Mapper<Value,ValueDto>
 */
final readonly class ValueMapper implements Mapper
{
    public function __construct(
        private readonly ValueSerializer $valueSerializer,
    ) {}

    #[\Override]
    public function toDto(object $object, MapperContext $context): ValueDto
    {
        /** @psalm-suppress MixedAssignment */
        $value = $object->getValue();

        if (\is_array($value)) {
            $value = array_map(
                fn($value) => $this->valueSerializer->serialize(
                    class: $context->getSummaryClass(),
                    dimension: $context->getCurrentField(),
                    value: $value,
                ),
                $value,
            );
        } else {
            $value = $this->valueSerializer->serialize(
                class: $context->getSummaryClass(),
                dimension: $context->getCurrentField(),
                value: $value,
            );
        }

        return new ValueDto($value);
    }

    #[\Override]
    public function fromDto(object $dto, MapperContext $context): Value
    {
        $value = $dto->value;

        if (\is_array($value)) {
            $value = array_map(
                /**
                 * @psalm-suppress MixedArgument
                 */
                fn($value): mixed => $this->valueSerializer->deserialize(
                    class: $context->getSummaryClass(),
                    dimension: $context->getCurrentField(),
                    /**
                     * @phpstan-ignore argument.type
                     */
                    identifier: $value,
                ),
                $value,
            );
        } else {
            /** @psalm-suppress MixedAssignment */
            $value = $this->valueSerializer->deserialize(
                class: $context->getSummaryClass(),
                dimension: $context->getCurrentField(),
                identifier: $value,
            );
        }

        return new Value($value);
    }
}
