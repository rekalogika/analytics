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

use Rekalogika\PivotTable\TableCubeAdapter\Model\TableCubeAdapterCube;
use Rekalogika\PivotTable\TableCubeAdapter\Model\TableCubeAdapterDimension;

final class CubeRegistry
{
    /**
     * @var array<string,TableCubeAdapterCube>
     */
    private array $cubes = [];

    public function __construct(
        private IdentityStrategy $identityStrategy,
        private TableCubeAdapterManager $manager,
    ) {}

    public function registerCube(TableCubeAdapterCube $cube): void
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
     * @param array<string,TableCubeAdapterDimension> $tuple
     */
    public function getCubeByTuple(array $tuple): TableCubeAdapterCube
    {
        $signature = $this->identityStrategy->getTupleSignature($tuple);

        return $this->cubes[$signature] ??= new TableCubeAdapterCube(
            manager: $this->manager,
            tuple: $tuple,
            value: null,
            null: true,
        );
    }
}
