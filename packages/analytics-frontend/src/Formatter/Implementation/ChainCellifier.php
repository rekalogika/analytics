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

namespace Rekalogika\Analytics\Frontend\Formatter\Implementation;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Formatter\CellifierAware;
use Rekalogika\Analytics\Frontend\Formatter\CellProperties;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Formatter\Unsupported;

final readonly class ChainCellifier implements Cellifier
{
    /**
     * @var list<Cellifier>
     */
    private array $cellifier;

    /**
     * @param iterable<Cellifier> $cellifiers
     */
    public function __construct(
        iterable $cellifiers,
        private Stringifier $stringifier,
    ) {
        $newCellifiers = [];

        foreach ($cellifiers as $cellifier) {
            if ($cellifier instanceof CellifierAware) {
                $cellifier = $cellifier->withCellifier($this);
            }

            $newCellifiers[] = $cellifier;
        }

        $this->cellifier = $newCellifiers;
    }

    #[\Override]
    public function toCell(mixed $input): CellProperties
    {
        foreach ($this->cellifier as $cellifier) {
            try {
                return $cellifier->toCell($input);
            } catch (Unsupported) {
            }
        }

        $result = $this->stringifier->toString($input);

        return new CellProperties(
            content: $result,
            type: DataType::TYPE_STRING,
            formatCode: null,
        );
    }
}
