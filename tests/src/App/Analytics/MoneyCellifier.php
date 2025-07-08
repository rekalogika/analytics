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

namespace Rekalogika\Analytics\Tests\App\Analytics;

use Brick\Money\Money;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Formatter\CellProperties;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;

final class MoneyCellifier implements Cellifier
{
    #[\Override]
    public function toCell(mixed $input): CellProperties
    {
        if (!$input instanceof Money) {
            throw new ValueNotSupportedException();
        }

        return  new CellProperties(
            content: $input->getAmount()->__toString(),
            type: DataType::TYPE_NUMERIC,
        );
    }
}
