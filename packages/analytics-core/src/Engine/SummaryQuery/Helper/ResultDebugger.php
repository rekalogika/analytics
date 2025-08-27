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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Helper;

use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\OrderedCoordinates;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Result\Table;

/**
 * A helper class for debugging various analytics result items.
 *
 * @api
 */
final readonly class ResultDebugger
{
    private function __construct() {}

    public static function debugAny(mixed $item): string
    {
        if ($item instanceof \Stringable) {
            return (string) $item;
        }

        if (\is_string($item)) {
            return $item;
        }

        if (\is_int($item) || \is_float($item)) {
            return (string) $item;
        }

        if ($item instanceof \BackedEnum) {
            return (string) $item->value;
        }

        if ($item instanceof \UnitEnum) {
            return $item->name;
        }

        return get_debug_type($item);
    }

    public static function debugDimension(Dimension $dimension): string
    {
        /** @psalm-suppress MixedAssignment */
        $member = $dimension->getMember();

        return self::debugAny($member);
    }

    /**
     * @return list<string>
     */
    public static function debugCoordinates(Coordinates|OrderedCoordinates $coordinates): array
    {
        $result = [];

        foreach ($coordinates as $dimension) {
            $result[] = self::debugDimension($dimension);
        }

        return $result;
    }

    public static function debugMeasure(Measure $measure): string
    {
        return self::debugAny($measure->getValue());
    }

    /**
     * @return list<string>
     */
    public static function debugMeasures(Measures $measures): array
    {
        $result = [];

        foreach ($measures as $measure) {
            $result[] = self::debugMeasure($measure);
        }

        return $result;
    }

    /**
     * @return array<string,list<string>>
     */
    public static function debugRow(Row $row): array
    {
        return [
            'coordinates' => self::debugCoordinates($row->getCoordinates()),
            'measures' => self::debugMeasures($row->getMeasures()),
        ];
    }

    /**
     * @return list<array<string,list<string>>>
     */
    public static function debugTable(Table $table): array
    {
        $result = [];

        foreach ($table as $row) {
            $result[] = self::debugRow($row);
        }

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    public static function debugCubeCell(CubeCell $cubeCell): array
    {
        $coordinates = $cubeCell->getCoordinates();
        $measures = $cubeCell->getMeasures();

        $coordinatesDebug = self::debugCoordinates($coordinates);
        $measuresDebug = self::debugMeasures($measures);

        return [
            'coordinates' => $coordinatesDebug,
            'measures' => $measuresDebug,
        ];
    }
}
