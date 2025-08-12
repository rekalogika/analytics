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

namespace Rekalogika\PivotTable\Util;

use Rekalogika\PivotTable\Contracts\Cube\CubeCell;

final readonly class CubeDebugger
{
    /**
     * @return array<string,mixed>
     */
    public static function debug(CubeCell $cubeCell): array
    {
        return (new self($cubeCell))->toArray();
    }

    public function __construct(private CubeCell $cubeCell) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $result = [
            'tuple' => $this->normalizeItem($this->cubeCell->getTuple()),
            'value' => $this->normalizeItem($this->cubeCell->getValue()),
        ];

        return $result;
    }

    private function normalizeItem(mixed $item): mixed
    {
        if (\is_array($item)) {
            return array_map(fn(mixed $i): mixed => $this->normalizeItem($i), $item);
        }

        if (\is_scalar($item)) {
            return var_export($item, true);
        }

        if (\is_null($item)) {
            return 'null';
        }

        if ($item instanceof \UnitEnum) {
            if ($item instanceof \BackedEnum) {
                return $item->value;
            }

            return $item->name;
        }

        if (!\is_object($item)) {
            return get_debug_type($item);
        }

        if ($item instanceof \Stringable) {
            return (string) $item;
        }

        if (method_exists($item, 'getContent')) {
            return $this->normalizeItem($item->getContent());
        }

        // @phpstan-ignore phpat.testPackageRekalogikaPivotTable
        // if ($item instanceof TranslatableInterface) {
        //     // @phpstan-ignore phpat.testPackageRekalogikaPivotTable
        //     return $item->trans(new NullTranslator());
        // }

        return \sprintf(
            '%s(%s)',
            get_debug_type($item),
            spl_object_id($item),
        );
    }
}
