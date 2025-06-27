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

namespace Rekalogika\Analytics\PivotTable\Model;

use Rekalogika\Analytics\PivotTable\TableVisitor;

final readonly class Member extends Property
{
    #[\Override]
    public function accept(TableVisitor $visitor): mixed
    {
        return $visitor->visitMember($this);
    }

    #[\Override]
    public function getContent(): mixed
    {
        return $this->getNode()->getDisplayMember();
    }
}
