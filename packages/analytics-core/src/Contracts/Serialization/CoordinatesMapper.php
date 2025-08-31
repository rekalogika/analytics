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

namespace Rekalogika\Analytics\Contracts\Serialization;

use Rekalogika\Analytics\Contracts\Dto\CoordinatesDto;
use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\Contracts\Result\Coordinates;

interface CoordinatesMapper
{
    public function toDto(Coordinates|Cell $input): CoordinatesDto;

    /**
     * @param class-string $summaryClass
     */
    public function fromDto(string $summaryClass, CoordinatesDto $dto): Cell;
}
