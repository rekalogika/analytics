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

namespace Rekalogika\Analytics\PivotTable\Model\ResultSet;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\PivotTable\Model\Label;
use Rekalogika\Analytics\PivotTable\TableVisitor;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class ResultSetLabel extends ResultSetProperty implements Label
{
    #[\Override]
    public function accept(TableVisitor $visitor): mixed
    {
        return $visitor->visitLabel($this);
    }

    #[\Override]
    public function getContent(): TranslatableInterface
    {
        $value = parent::getContent();

        if (!$value instanceof TranslatableInterface) {
            throw new LogicException('The label value must implement TranslatableInterface.');
        }

        return $value;
    }
}
