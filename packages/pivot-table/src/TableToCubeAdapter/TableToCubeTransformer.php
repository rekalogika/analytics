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

namespace Rekalogika\PivotTable\TableToCubeAdapter;

use Rekalogika\PivotTable\Contracts\Row;
use Rekalogika\PivotTable\Contracts\Table;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterCube;

final readonly class TableToCubeTransformer
{
    public function __construct(
        private Table $table,
        private MeasureMemberRepository $measureMemberRepository,
        private TableToCubeAdapterManager $manager,
        private DimensionRepository $dimensionRepository,
    ) {}

    /**
     * @return iterable<TableToCubeAdapterCube>
     */
    public function transform(): iterable
    {
        // create the cube for each row
        foreach ($this->table->getRows() as $row) {
            $measures = iterator_to_array($row->getMeasures());

            if ($measures === []) {
                continue;
            }

            foreach ($this->transformRowToCubes($row) as $cube) {
                yield $cube;
            }
        }
    }

    /**
     * @return iterable<TableToCubeAdapterCube>
     */
    private function transformRowToCubes(Row $row): iterable
    {
        // create the cube without the value

        $tuple = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($row->getDimensions() as $dimensionName => $member) {
            $dimension = $this->dimensionRepository
                ->getDimension($dimensionName, $member);

            $tuple[$dimensionName] = $dimension;
        }

        yield new TableToCubeAdapterCube(
            manager: $this->manager,
            tuple: $tuple,
            value: null,
            null: false,
        );

        // create the cube with each of the values

        /** @psalm-suppress MixedAssignment */
        foreach ($row->getMeasures() as $measureName => $value) {
            $measureMember = $this->measureMemberRepository
                ->getMeasureMember($measureName);

            $dimension = $this->dimensionRepository
                ->getDimension('@values', $measureMember);

            $tuple['@values'] = $dimension;

            $a = new TableToCubeAdapterCube(
                manager: $this->manager,
                tuple: $tuple,
                value: $value,
                null: false,
            );

            yield $a;
        }
    }
}
