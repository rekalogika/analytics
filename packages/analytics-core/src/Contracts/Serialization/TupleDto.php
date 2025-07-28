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

use Doctrine\Common\Collections\Expr\Expression;

final readonly class TupleDto
{
    /**
     * @param array<string,string> $members Key is dimension name, value is the
     * serialized raw member value.
     */
    public function __construct(
        private array $members,
        private ?Expression $condition,
    ) {}

    /**
     * @return array<string,string>
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function getCondition(): ?Expression
    {
        return $this->condition;
    }
}
