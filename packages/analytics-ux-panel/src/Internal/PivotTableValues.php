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

namespace Rekalogika\Analytics\UX\PanelBundle\Internal;

use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class PivotTableValues
{
    public function getName(): string
    {
        return '@values';
    }

    public function getLabel(): TranslatableInterface
    {
        return new TranslatableMessage('Values');
    }

    public function getType(): PivotTableItemType
    {
        return PivotTableItemType::Values;
    }

    public function getChoices(): null
    {
        return null;
    }
}
