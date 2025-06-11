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

namespace Rekalogika\Analytics\Bundle\UI\Filter\Model;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Rekalogika\Analytics\Time\TimeBin;

final readonly class NumberRange implements \Stringable
{
    /**
     * @param class-string<TimeBin|RecurringTimeBin> $typeClass
     */
    public function __construct(
        private readonly string $dimension,
        private string $typeClass,
        private int $start,
        private int $end,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s-%s', $this->start, $this->end);
    }

    private function getStartObject(): TimeBin|RecurringTimeBin
    {
        return ($this->typeClass)::createFromDatabaseValue($this->start);
    }

    private function getEndObject(): TimeBin|RecurringTimeBin
    {
        return ($this->typeClass)::createFromDatabaseValue($this->end);
    }

    public function createExpression(): Expression
    {
        return Criteria::expr()->andX(
            Criteria::expr()->gte($this->dimension, $this->getStartObject()),
            Criteria::expr()->lte($this->dimension, $this->getEndObject()),
        );
    }
}
