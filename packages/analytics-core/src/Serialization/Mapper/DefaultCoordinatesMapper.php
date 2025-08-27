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
use Rekalogika\Analytics\Contracts\Dto\CoordinatesDto;
use Rekalogika\Analytics\Contracts\Dto\ExpressionDto;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Serialization\Implementation\NullRow;
use Rekalogika\Analytics\Serialization\Mapper\Implementation\ChainMapper;

final readonly class DefaultCoordinatesMapper implements CoordinatesMapper
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
    public function toDto(Coordinates $coordinates): CoordinatesDto
    {
        $class = $coordinates->getSummaryClass();

        $members = [];

        foreach ($coordinates as $name => $dimension) {
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

        $condition = $coordinates->getPredicate();

        if ($condition !== null) {
            $mapperContext = new MapperContext(
                summaryClass: $class,
            );

            $condition = $this->mapper->toDto($condition, $mapperContext);

            if (!$condition instanceof ExpressionDto) {
                throw new UnexpectedValueException('Expected ExpressionDto, got ' . \get_class($condition));
            }
        }

        return new CoordinatesDto(
            members: $members,
            predicate: $condition,
        );
    }

    #[\Override]
    public function fromDto(string $summaryClass, CoordinatesDto $dto): Row
    {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        // create query
        $query = $this->summaryManager
            ->createQuery()
            ->from($summaryClass);

        // add where condition
        $conditionDto = $dto->getPredicate();
        $condition = null;

        if ($conditionDto !== null) {
            $mapperContext = new MapperContext(
                summaryClass: $summaryClass,
            );

            $condition = $this->mapper->fromDto($conditionDto, $mapperContext);

            if (!$condition instanceof Expression) {
                throw new UnexpectedValueException('Expected Expression, got ' . \get_class($condition));
            }

            $query->dice($condition);
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

            $query->addDimension($dimensionName);

            $query->andDice(Criteria::expr()->eq(
                $dimensionName,
                $rawMember,
            ));

            /** @psalm-suppress MixedAssignment */
            $dimensionMembers[$dimensionName] = $rawMember;
        }

        // execute
        return $query->getResult()->getTable()->first() ?? new NullRow(
            summaryMetadata: $metadata,
            dimensionMembers: $dimensionMembers,
            condition: $condition,
        );
    }
}
