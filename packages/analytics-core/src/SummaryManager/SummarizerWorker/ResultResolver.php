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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\HydratorResult;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class ResultResolver
{
    public function __construct(
        private readonly SummaryQuery $query,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {}

    /**
     * @param iterable<HydratorResult> $hydratorResults
     * @return iterable<array<string,array{mixed,mixed}>>
     */
    public function resolveResult(iterable $hydratorResults): iterable
    {
        foreach ($hydratorResults as $hydratorResult) {
            yield $this->mapArrayToSummaryObjectAndBackAgain($hydratorResult);
        }
    }

    /**
     * @return array<string,array{mixed,mixed}>
     */
    private function mapArrayToSummaryObjectAndBackAgain(
        HydratorResult $hydratorResult
    ): array {
        $keys = array_filter(
            [...$this->query->getGroupBy(), ...$this->query->getSelect()],
            fn (string $key) => $key !== '@values',
        );

        $object = $hydratorResult->getObject();
        $rawValues = $hydratorResult->getRawValues();

        $outputArray = [];

        foreach ($keys as $key) {
            /** @psalm-suppress MixedAssignment */
            $rawValue = $rawValues[$key] ?? null;

            $outputArray[$key] = [
                $rawValue,
                $this->propertyAccessor->getValue($object, $key),
            ];
        }

        return $outputArray;
    }
}
