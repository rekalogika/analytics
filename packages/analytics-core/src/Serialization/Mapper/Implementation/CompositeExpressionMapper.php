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

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Dto\CompositeExpressionDto;
use Rekalogika\Analytics\Contracts\Dto\ExpressionDto;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Serialization\Mapper\Mapper;
use Rekalogika\Analytics\Serialization\Mapper\MapperContext;

/**
 * @implements Mapper<CompositeExpression,CompositeExpressionDto>
 */
final readonly class CompositeExpressionMapper implements Mapper
{
    /**
     * @param Mapper<object,object> $mapper
     */
    public function __construct(
        private readonly Mapper $mapper,
    ) {}

    #[\Override]
    public function toDto(object $object, MapperContext $context): CompositeExpressionDto
    {
        $expressions = [];

        foreach ($object->getExpressionList() as $expr) {
            $dto = $this->mapper->toDto($expr, $context);

            if (!$dto instanceof ExpressionDto) {
                throw new LogicException('Expected ExpressionDto, got ' . \get_class($dto));
            }

            $expressions[] = $dto;
        }

        return new CompositeExpressionDto(
            type: $object->getType(),
            expressions: $expressions,
        );
    }

    #[\Override]
    public function fromDto(object $dto, MapperContext $context): CompositeExpression
    {
        $expressions = [];

        foreach ($dto->expressions as $expr) {
            $expression = $this->mapper->fromDto($expr, $context);

            if (!$expression instanceof Expression) {
                throw new LogicException('Expected Expression, got ' . \get_class($expression));
            }

            $expressions[] = $expression;
        }

        return new CompositeExpression(
            type: $dto->type,
            expressions: $expressions,
        );
    }
}
