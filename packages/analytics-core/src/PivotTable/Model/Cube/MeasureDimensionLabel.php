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

namespace Rekalogika\Analytics\PivotTable\Model\Cube;

use Rekalogika\Analytics\PivotTable\Model\Label;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class MeasureDimensionLabel implements Label
{
    public function __construct(private TranslatableInterface $label) {}

    #[\Override]
    public function getContent(): TranslatableInterface
    {
        return $this->label;
    }
}
