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

namespace Rekalogika\Analytics\Frontend\Util;

use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\PivotTable\Contracts\Cube\SubtotalDescriptionResolver;

/**
 * We don't have customizable subtotal description yet.
 */
final readonly class HardcodedSubtotalDescriptionResolver implements SubtotalDescriptionResolver
{
    #[\Override]
    public function getSubtotalDescription(string $dimensionName): mixed
    {
        return new TranslatableMessage('Subtotal');
    }
}
