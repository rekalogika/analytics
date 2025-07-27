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

use Rekalogika\Analytics\Contracts\MemberValuesManager;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Contracts\Serialization\TupleDto;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Serialization\TupleSerializer;

final readonly class DefaultTupleSerializer implements TupleSerializer
{
    public function __construct(
        private MemberValuesManager $memberValuesManager,
    ) {}

    public function serialize(Tuple $tuple): TupleDto
    {
        $class = $tuple->getSummaryClass();

        $members = [];

        foreach ($tuple as $dimension) {
            $dimensionName = $dimension->getName();
            /** @psalm-suppress MixedAssignment */
            $dimensionMember = $dimension->getRawMember();

            // Serialize the value to a string representation
            $serializedValue = $this->memberValuesManager->getIdentifierFromValue(
                class: $class,
                dimension: $dimensionName,
                value: $dimensionMember,
            );

            if ($serializedValue === null) {
                throw new \UnexpectedValueException(\sprintf(
                    'The value for dimension "%s" in class "%s" is not serializable.',
                    $dimensionName,
                    $class,
                ));
            }

            $members[$dimensionName] = $serializedValue;
        }


        return new TupleDto(
            summaryClass: $tuple->getSummaryClass(),
            members: $members,
            condition: $tuple->getCondition(),
        );
    }

    public function deserialize(TupleDto $dto): Row {}
}
