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

namespace Rekalogika\Analytics\Serialization\Mapper;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Dto\ExpressionDto;
use Rekalogika\Analytics\Contracts\Dto\TupleDto;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Contracts\Serialization\TupleMapper;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Serialization\Mapper\Implementation\ChainMapper;

final readonly class DefaultTupleMapper implements TupleMapper
{
    /**
     * @var Mapper<object,object>
     */
    private Mapper $mapper;

    public function __construct(
        private ValueSerializer $valueSerializer,
        private SummaryManager $summaryManager,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {
        $this->mapper = new ChainMapper($this->valueSerializer);
    }

    #[\Override]
    public function toDto(Tuple $tuple): TupleDto
    {
        $class = $tuple->getSummaryClass();

        $members = [];

        foreach ($tuple as $name => $dimension) {
            if ($name === '@values') {
                continue;
            }

            $dimensionName = $dimension->getName();
            /** @psalm-suppress MixedAssignment */
            $dimensionMember = $dimension->getRawMember();

            $serializedValue = $this->valueSerializer->serialize(
                class: $class,
                dimension: $dimensionName,
                value: $dimensionMember,
            );

            $members[$dimensionName] = $serializedValue;
        }

        $condition = $tuple->getCondition();

        if ($condition !== null) {
            $mapperContext = new MapperContext(
                summaryClass: $class,
            );

            $condition = $this->mapper->toDto($condition, $mapperContext);

            if (!$condition instanceof ExpressionDto) {
                throw new UnexpectedValueException('Expected ExpressionDto, got ' . \get_class($condition));
            }
        }

        return new TupleDto(
            members: $members,
            condition: $condition,
        );
    }

    #[\Override]
    public function fromDto(string $summaryClass, TupleDto $dto): Result
    {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        // create query
        $query = $this->summaryManager
            ->createQuery()
            ->from($summaryClass);

        // add where condition
        $conditionDto = $dto->getCondition();
        $condition = null;

        if ($conditionDto !== null) {
            $mapperContext = new MapperContext(
                summaryClass: $summaryClass,
            );

            $condition = $this->mapper->fromDto($conditionDto, $mapperContext);

            if (!$condition instanceof Expression) {
                throw new UnexpectedValueException('Expected Expression, got ' . \get_class($condition));
            }

            $query->where($condition);
        }

        // add group by
        $dimensionMembers = [];

        foreach ($dto->getMembers() as $dimensionName => $serializedValue) {
            /** @psalm-suppress MixedAssignment */
            $rawMember = $this->valueSerializer->deserialize(
                class: $summaryClass,
                dimension: $dimensionName,
                identifier: $serializedValue,
            );

            $query->addGroupBy($dimensionName);

            $query->andWhere(Criteria::expr()->eq(
                $dimensionName,
                $rawMember,
            ));

            /** @psalm-suppress MixedAssignment */
            $dimensionMembers[$dimensionName] = $rawMember;
        }

        // select all measures
        foreach ($metadata->getMeasures() as $measure) {
            $query->addSelect($measure->getName());
        }

        // execute
        return $query->getResult();
    }
}
