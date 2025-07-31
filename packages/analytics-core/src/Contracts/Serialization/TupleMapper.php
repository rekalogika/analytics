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

use Rekalogika\Analytics\Contracts\Dto\TupleDto;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Contracts\Result\Tuple;

interface TupleMapper
{
    public function toDto(Tuple $tuple): TupleDto;

    /**
     * @param class-string $summaryClass
     */
    public function fromDto(string $summaryClass, TupleDto $dto): Result;
}
