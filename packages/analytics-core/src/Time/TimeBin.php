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

namespace Rekalogika\Analytics\Time;

use Rekalogika\Analytics\Contracts\Model\Bin;
use Rekalogika\Analytics\Contracts\Model\DatabaseValueAware;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @extends Bin<\DateTimeInterface>
 * @extends DatabaseValueAware<int>
 */
interface TimeBin extends
    \Stringable,
    TranslatableInterface,
    Bin,
    DatabaseValueAware
{
    public const string TYPE = '__needs_to_be_overriden__';

    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static;

    public static function createFromDatabaseValue(int $databaseValue): static;

    public function withTimeZone(\DateTimeZone $timeZone): static;

    public function getStart(): \DateTimeInterface;

    public function getEnd(): \DateTimeInterface;
}
