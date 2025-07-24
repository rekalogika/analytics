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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\NormalRow;
use Rekalogika\Analytics\Contracts\Result\NormalTable;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Result\Table;
use Rekalogika\Analytics\Contracts\Result\Tuple;

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
    public static function debugTuple(Tuple $tuple): array
    {
        $result = [];

        foreach ($tuple as $dimension) {
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
            'tuple' => self::debugTuple($row),
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
     * @return array<string,list<string>|string>
     */
    public static function debugNormalRow(NormalRow $row): array
    {
        return [
            'tuple' => self::debugTuple($row),
            'measure' => self::debugMeasure($row->getMeasure()),
        ];
    }

    public static function debugNormalTable(NormalTable $table): array
    {
        $result = [];

        foreach ($table as $row) {
            $result[] = self::debugNormalRow($row);
        }

        return $result;
    }
}
