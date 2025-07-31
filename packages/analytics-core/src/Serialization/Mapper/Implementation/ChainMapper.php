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
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Rekalogika\Analytics\Contracts\Dto\ComparisonDto;
use Rekalogika\Analytics\Contracts\Dto\CompositeExpressionDto;
use Rekalogika\Analytics\Contracts\Dto\ValueDto;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Serialization\Mapper\Mapper;
use Rekalogika\Analytics\Serialization\Mapper\MapperContext;

/**
 * @implements Mapper<object,object>
 */
final readonly class ChainMapper implements Mapper
{
    private ComparisonMapper $comparisonMapper;
    private CompositeExpressionMapper $compositeExpressionMapper;
    private ValueMapper $valueMapper;

    public function __construct(
        private readonly ValueSerializer $valueSerializer,
    ) {
        $this->comparisonMapper = new ComparisonMapper($this);
        $this->compositeExpressionMapper = new CompositeExpressionMapper($this);
        $this->valueMapper = new ValueMapper($this->valueSerializer);
    }

    #[\Override]
    public function toDto(object $object, MapperContext $context): object
    {
        if ($object instanceof Comparison) {
            return $this->comparisonMapper->toDto($object, $context);
        }

        if ($object instanceof CompositeExpression) {
            return $this->compositeExpressionMapper->toDto($object, $context);
        }

        if ($object instanceof Value) {
            return $this->valueMapper->toDto($object, $context);
        }

        throw new LogicException('Unsupported object type: ' . \get_class($object));
    }

    #[\Override]
    public function fromDto(object $dto, MapperContext $context): object
    {
        if ($dto instanceof ComparisonDto) {
            return $this->comparisonMapper->fromDto($dto, $context);
        }

        if ($dto instanceof CompositeExpressionDto) {
            return $this->compositeExpressionMapper->fromDto($dto, $context);
        }

        if ($dto instanceof ValueDto) {
            return $this->valueMapper->fromDto($dto, $context);
        }

        throw new LogicException('Unsupported DTO type: ' . \get_class($dto));
    }
}
