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

namespace Rekalogika\PivotTable\TableToCubeAdapter\Helper;

use Rekalogika\PivotTable\Contracts\Table\Table;
use Rekalogika\PivotTable\TableToCubeAdapter\IdentityStrategy;
use Rekalogika\PivotTable\TableToCubeAdapter\Implementation\DefaultIdentityStrategy;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterCube;

final readonly class TableToCubeAdapterManager
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

    public function getApexCube(): TableToCubeAdapterCube
    {
        return $this->cubeRegistry->getCubeByTuple([]);
    }

    public function slice(
        TableToCubeAdapterCube $base,
        string $dimensionName,
        mixed $dimensionMember,
    ): TableToCubeAdapterCube {
        $tuple = $base->getTuple();

        $tuple[$dimensionName] = $this->dimensionRepository
            ->getDimension($dimensionName, $dimensionMember);

        return $this->cubeRegistry->getCubeByTuple($tuple);
    }

    /**
     * @return iterable<TableToCubeAdapterCube>
     */
    public function drillDown(
        TableToCubeAdapterCube $base,
        string $dimensionName,
    ): iterable {
        $members = $this->memberRegistry->getMembers($dimensionName);

        /** @psalm-suppress MixedAssignment */
        foreach ($members as $member) {
            yield $this->slice($base, $dimensionName, $member);
        }
    }

    public function rollUp(
        TableToCubeAdapterCube $base,
        string $dimensionName,
    ): TableToCubeAdapterCube {
        $tuple = $base->getTuple();

        unset($tuple[$dimensionName]);

        return $this->cubeRegistry->getCubeByTuple($tuple);
    }
}
