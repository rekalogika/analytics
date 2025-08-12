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

use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterCube;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterDimension;

final class CubeRegistry
{
    /**
     * @var array<string,TableToCubeAdapterCube>
     */
    private array $cubes = [];

    public function __construct(
        private IdentityStrategy $identityStrategy,
        private TableToCubeAdapterManager $manager,
    ) {}

    public function registerCube(TableToCubeAdapterCube $cube): void
    {
        $signature = $this->identityStrategy->getTupleSignature($cube->getTuple());

        if (isset($this->cubes[$signature])) {
            throw new \RuntimeException(\sprintf(
                'Cube with signature "%s" already exists.',
                $signature,
            ));
        }

        $this->cubes[$signature] = $cube;
    }

    /**
     * @param array<string,TableToCubeAdapterDimension> $tuple
     */
    public function getCubeByTuple(array $tuple): TableToCubeAdapterCube
    {
        $signature = $this->identityStrategy->getTupleSignature($tuple);

        return $this->cubes[$signature] ??= new TableToCubeAdapterCube(
            manager: $this->manager,
            tuple: $tuple,
            value: null,
            null: true,
        );
    }
}
