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

namespace Rekalogika\Analytics\Bundle\Formatter\Implementation;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Rekalogika\Analytics\Bundle\Formatter\BackendCellifier;
use Rekalogika\Analytics\Bundle\Formatter\BackendStringifier;
use Rekalogika\Analytics\Bundle\Formatter\CellProperties;

final readonly class DefaultBackendCellifier implements BackendCellifier
{
    #[\Override]
    public function toCell(mixed $input): ?CellProperties
    {
        if ($input === null) {
            return new CellProperties(
                type: DataType::TYPE_NULL,
            );
        }

        if ($input === true) {
            return new CellProperties(
                type: DataType::TYPE_BOOL,
                content: '1',
            );
        }

        if ($input === false) {
            return new CellProperties(
                type: DataType::TYPE_BOOL,
                content: '0',
            );
        }

        return null;
    }
}
