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

namespace Rekalogika\Analytics\Serialization\Implementation;

use Doctrine\Common\Collections\Criteria;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Contracts\Serialization\TupleDto;
use Rekalogika\Analytics\Contracts\Serialization\TupleSerializer;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final readonly class DefaultTupleSerializer implements TupleSerializer
{
    public function __construct(
        private ValueSerializer $valueSerializer,
        private SummaryManager $summaryManager,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    #[\Override]
    public function serialize(Tuple $tuple): TupleDto
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

            // Serialize the value to a string representation
            $serializedValue = $this->valueSerializer->serialize(
                class: $class,
                dimension: $dimensionName,
                value: $dimensionMember,
            );

            $members[$dimensionName] = $serializedValue;
        }

        return new TupleDto(
            members: $members,
            condition: $tuple->getCondition(),
        );
    }

    #[\Override]
    public function deserialize(string $summaryClass, TupleDto $dto): Row
    {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        // create query
        $query = $this->summaryManager
            ->createQuery()
            ->from($summaryClass);

        // add where condition
        $condition = $dto->getCondition();

        if ($condition !== null) {
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
        $result = $query->getResult();
        $table = $result->getTable();
        $rows = iterator_to_array($table, false);

        return $rows[0] ?? new NullRow(
            summaryMetadata: $metadata,
            dimensionMembers: $dimensionMembers,
            condition: $condition,
        );
    }
}
