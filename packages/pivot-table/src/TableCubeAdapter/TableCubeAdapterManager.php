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

namespace Rekalogika\PivotTable\TableCubeAdapter;

use Rekalogika\PivotTable\Contracts\Table;
use Rekalogika\PivotTable\TableCubeAdapter\Implementation\DefaultIdentityStrategy;
use Rekalogika\PivotTable\TableCubeAdapter\Model\TableCubeAdapterCube;

final readonly class TableCubeAdapterManager
{
    private CubeRegistry $cubeRegistry;
    private MeasureMemberRepository $measureMemberRepository;
    private TableToCubeTransformer $tableToCubeTransformer;
    private MemberRegistry $memberRegistry;
    private DimensionRepository $dimensionRepository;

    public function __construct(
        private Table $table,
        private IdentityStrategy $identityStrategy = new DefaultIdentityStrategy(),
    ) {
        $this->dimensionRepository = new DimensionRepository(
            table: $this->table,
            identityStrategy: $this->identityStrategy,
        );

        $this->cubeRegistry = new CubeRegistry(
            identityStrategy: $this->identityStrategy,
            manager: $this,
        );

        $this->measureMemberRepository = new MeasureMemberRepository(
            table: $this->table,
        );

        $this->tableToCubeTransformer = new TableToCubeTransformer(
            table: $this->table,
            measureMemberRepository: $this->measureMemberRepository,
            manager: $this,
            dimensionRepository: $this->dimensionRepository,
        );

        $this->memberRegistry = new MemberRegistry(
            identityStrategy: $this->identityStrategy,
        );

        foreach ($this->tableToCubeTransformer->transform() as $cube) {
            $this->cubeRegistry->registerCube($cube);
            $this->memberRegistry->registerCubeMembers($cube);
        }
    }

    public function getApexCube(): TableCubeAdapterCube
    {
        return $this->cubeRegistry->getCubeByTuple([]);
    }

    public function slice(
        TableCubeAdapterCube $base,
        string $dimensionName,
        mixed $dimensionMember,
    ): TableCubeAdapterCube {
        $tuple = $base->getTuple();

        $tuple[$dimensionName] = $this->dimensionRepository
            ->getDimension($dimensionName, $dimensionMember);

        return $this->cubeRegistry->getCubeByTuple($tuple);
    }

    /**
     * @return iterable<TableCubeAdapterCube>
     */
    public function drillDown(
        TableCubeAdapterCube $base,
        string $dimensionName,
    ): iterable {
        $members = $this->memberRegistry->getMembers($dimensionName);

        /** @psalm-suppress MixedAssignment */
        foreach ($members as $member) {
            yield $this->slice($base, $dimensionName, $member);
        }
    }

    public function rollUp(
        TableCubeAdapterCube $base,
        string $dimensionName,
    ): TableCubeAdapterCube {
        $tuple = $base->getTuple();

        unset($tuple[$dimensionName]);

        return $this->cubeRegistry->getCubeByTuple($tuple);
    }
}
