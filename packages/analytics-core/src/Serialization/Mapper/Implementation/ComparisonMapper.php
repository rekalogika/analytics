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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Rekalogika\Analytics\Contracts\Dto\ComparisonDto;
use Rekalogika\Analytics\Contracts\Dto\ValueDto;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Serialization\Mapper\Mapper;
use Rekalogika\Analytics\Serialization\Mapper\MapperContext;

/**
 * @implements Mapper<Comparison,ComparisonDto>
 */
final readonly class ComparisonMapper implements Mapper
{
    /**
     * @param Mapper<object,object> $mapper
     */
    public function __construct(
        private readonly Mapper $mapper,
    ) {}

    #[\Override]
    public function toDto(object $object, MapperContext $context): ComparisonDto
    {
        $context = $context->withCurrentField($object->getField());
        $value = $this->mapper->toDto($object->getValue(), $context);

        if (!$value instanceof ValueDto) {
            throw new UnexpectedValueException('Expected ValueDto, got ' . \get_class($value));
        }

        return new ComparisonDto(
            field: $object->getField(),
            operator: $object->getOperator(),
            value: $value,
        );
    }

    #[\Override]
    public function fromDto(object $dto, MapperContext $context): Comparison
    {
        $context = $context->withCurrentField($dto->field);
        $value = $this->mapper->fromDto($dto->value, $context);

        if (!$value instanceof Value) {
            throw new UnexpectedValueException('Expected Value, got ' . \get_class($value));
        }

        return new Comparison(
            field: $dto->field,
            op: $dto->operator,
            value: $value,
        );
    }
}
