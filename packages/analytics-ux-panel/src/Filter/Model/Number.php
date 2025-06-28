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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\Model;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Rekalogika\Analytics\Time\TimeBin;

final readonly class Number implements \Stringable
{
    /**
     * @param class-string<TimeBin|RecurringTimeBin> $typeClass
     */
    public function __construct(
        private readonly string $dimension,
        private string $typeClass,
        private int $number,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->number;
    }

    private function getObject(): TimeBin|RecurringTimeBin
    {
        return ($this->typeClass)::createFromDatabaseValue($this->number);
    }

    public function createExpression(): Expression
    {
        return Criteria::expr()->eq($this->dimension, $this->getObject());
    }
}
