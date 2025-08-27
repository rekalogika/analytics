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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

trait MeasuresTrait
{
    private ?DefaultMeasure $measure = null;

    public function getMeasure(): DefaultMeasure
    {
        if ($this->measure !== null) {
            return $this->measure;
        }

        $measureName = $this->getCoordinates()->getMeasureName();

        // does not have @values in the coordinates
        if ($measureName === null) {
            $measure = DefaultMeasure::createMultiple();
        } else {
            $measure = $this->getMeasures()->get($measureName)
                ?? throw new InvalidArgumentException(
                    \sprintf('Measure with name "%s" does not exist.', $measureName),
                );
        }

        return $this->measure = $measure;
    }
}
