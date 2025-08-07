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

use Rekalogika\PivotTable\Contracts\Row;
use Rekalogika\PivotTable\Contracts\Table;

final class RowRepository
{
    /**
     * @var array<string,Row>
     */
    private array $row = [];

    /**
     * @var array<string,true>
     */
    private array $measures = [];

    private DimensionRepository $dimensions;

    public function __construct(
        Table $table,
        private readonly IdentityStrategy $identityStrategy,
    ) {
        $this->dimensions = new DimensionRepository($identityStrategy);

        foreach ($table->getRows() as $row) {
            $this->recordRow($row);
        }

        foreach (array_keys($this->measures) as $measure) {
            $this->dimensions->recordDimension('@values', $measure);
        }
    }

    private function recordRow(Row $row): void
    {
        $tuple = iterator_to_array($row->getDimensions(), true);

        $signature = $this->identityStrategy
            ->getMembersSignature($tuple);

        if (isset($this->row[$signature])) {
            throw new \InvalidArgumentException(\sprintf(
                'Signature collission. Row with signature "%s" already exists.',
                $signature,
            ));
        }

        $this->row[$signature] = $row;

        /** @psalm-suppress MixedAssignment */
        foreach ($row->getDimensions() as $key => $member) {
            $this->dimensions->recordDimension($key, $member);
        }

        foreach ($row->getMeasures() as $key => $_) {
            $this->measures[$key] = true;
        }
    }

    public function getDimensionRepository(): DimensionRepository
    {
        return $this->dimensions;
    }

    /**
     * @param array<string,mixed> $tuple
     */
    public function getRow(array $tuple): ?Row
    {
        // remove @values
        unset($tuple['@values']);
        $signature = $this->identityStrategy->getMembersSignature($tuple);

        return $this->row[$signature] ?? null;
    }

    /**
     * @param array<string,mixed> $tuple
     * @throws \InvalidArgumentException
     */
    public function getRowOrFail(array $tuple): Row
    {
        $row = $this->getRow($tuple);

        if ($row === null) {
            throw new \InvalidArgumentException(\sprintf(
                'Row with members "%s" not found.',
                json_encode($tuple, JSON_THROW_ON_ERROR),
            ));
        }

        return $row;
    }
}
