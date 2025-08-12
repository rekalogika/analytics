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

namespace Rekalogika\PivotTable\TableCubeAdapter\Model;

use Rekalogika\PivotTable\Contracts\Cube\Cube;
use Rekalogika\PivotTable\TableCubeAdapter\TableCubeAdapterManager;

final readonly class TableCubeAdapterCube implements Cube
{
    /**
     * @param array<string,TableCubeAdapterDimension> $tuple
     */
    public function __construct(
        private TableCubeAdapterManager $manager,
        private array $tuple,
        private mixed $value,
        private bool $null,
    ) {}

    /**
     * @return array<string,TableCubeAdapterDimension>
     */
    #[\Override]
    public function getTuple(): array
    {
        return $this->tuple;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->null;
    }

    #[\Override]
    public function slice(
        string $dimensionName,
        mixed $member,
    ): TableCubeAdapterCube {
        return $this->manager->slice(
            base: $this,
            dimensionName: $dimensionName,
            dimensionMember: $member,
        );
    }

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        return $this->manager->drillDown(
            base: $this,
            dimensionName: $dimensionName,
        );
    }

    #[\Override]
    public function rollUp(string $dimensionName): TableCubeAdapterCube
    {
        return $this->manager->rollUp(
            base: $this,
            dimensionName: $dimensionName,
        );
    }
}
