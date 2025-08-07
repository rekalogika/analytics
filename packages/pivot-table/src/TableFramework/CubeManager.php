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

namespace Rekalogika\PivotTable\TableFramework;

use Rekalogika\PivotTable\Contracts\Table;
use Rekalogika\PivotTable\TableFramework\Implementation\DefaultIdentityStrategy;

final class CubeManager
{
    private RowRepository $rowRepository;
    private DimensionRepository $dimensionRepository;

    public function __construct(
        private readonly Table $table,
        ?IdentityStrategy $identityStrategy = null,
    ) {
        $identityStrategy ??= new DefaultIdentityStrategy();

        $this->rowRepository = new RowRepository($table, $identityStrategy);
        $this->dimensionRepository = $this->rowRepository
            ->getDimensionRepository();
    }

    public function getRowRepository(): RowRepository
    {
        return $this->rowRepository;
    }

    public function getDimensionRepository(): DimensionRepository
    {
        return $this->dimensionRepository;
    }

    public function getLegend(string $dimension): mixed
    {
        return $this->table->getLegend($dimension);
    }

    public function createApexCube(): Cube
    {
        return new Cube($this, []);
    }

    /**
     * @param array<string,mixed> $tuple
     * @return list<Cube>
     */
    public function drillDown(array $tuple, string $dimension): array
    {
        $members = $this->dimensionRepository->getMembers($dimension);

        $result = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($members as $member) {
            $newTuple = $tuple;
            $newTuple[$dimension] = $member;

            $result[] = new Cube($this, $newTuple);
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $tuple
     * @param mixed $member
     */
    public function slice(
        array $tuple,
        string $dimensionName,
        mixed $member,
    ): Cube {
        /** @psalm-suppress MixedAssignment */
        $tuple[$dimensionName] = $member;

        return new Cube($this, $tuple);
    }

    /**
     * @param array<string,mixed> $tuple
     * @param list<mixed> $members
     * @return iterable<Cube>
     */
    public function multipleSlices(
        array $tuple,
        string $dimensionName,
        array $members,
    ): iterable {
        /** @psalm-suppress MixedAssignment */
        foreach ($members as $member) {
            yield $this->slice($tuple, $dimensionName, $member);
        }
    }

    /**
     * @param array<string,mixed> $tuple
     * @param iterable<Cube> $cubes
     * @return list<Cube>
     */
    public function multipleSlicesFromCubes(
        array $tuple,
        string $dimensionName,
        iterable $cubes,
    ): array {
        $result = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($cubes as $cube) {
            if ($dimensionName === '@values') {
                $measureName = $cube->getTuple()['@values'] ?? null;

                if ($measureName === null) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Measure name not found in cube tuple for dimension "%s".',
                        $dimensionName,
                    ));
                }

                $member = $measureName;
            } else {
                $member = $cube->getMember($dimensionName);
            }
            $result[] = $this->slice($tuple, $dimensionName, $member);
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $tuple
     * @param list<string> $dimensions
     */
    public function rollUp(array $tuple, array $dimensions): Cube
    {
        foreach ($dimensions as $dimension) {
            if (!isset($tuple[$dimension])) {
                throw new \InvalidArgumentException(\sprintf(
                    'Dimension "%s" not found in tuple.',
                    $dimension,
                ));
            }

            unset($tuple[$dimension]);
        }

        return new Cube($this, $tuple);
    }


    /**
     * @param array<string,mixed> $tuple
     */
    public function getValue(array $tuple): mixed
    {
        $measure = $tuple['@values'] ?? null;

        // if not narrowed down to a single measure
        if ($measure === null) {
            return null;
        }

        if (!\is_string($measure)) {
            throw new \InvalidArgumentException(\sprintf(
                'Expected string for measure, "%s" given.',
                \gettype($measure),
            ));
        }

        $row = $this->rowRepository->getRow($tuple);

        if ($row === null) {
            return null;
        }

        $measures = iterator_to_array($row->getMeasures(), true);

        return $measures[$measure] ?? null;
    }

    /**
     * @param array<string,mixed> $tuple
     */
    public function isNull(array $tuple): bool
    {
        $row = $this->rowRepository->getRow($tuple);

        return $row === null;
    }
}
