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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory;

use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;

/**
 * Creates a null measure for each of the measures
 */
final class NullMeasureCollection
{
    /**
     * @var array<string,DefaultMeasure>
     */
    private array $nullMeasures = [];

    public function collectMeasure(DefaultMeasure $measure): void
    {
        $this->nullMeasures[$measure->getName()]
            ??= DefaultMeasure::createNullFromSelf($measure);
    }

    public function getNullMeasure(string $name): DefaultMeasure
    {
        return $this->nullMeasures[$name] ?? throw new \InvalidArgumentException(
            \sprintf('Measure with name "%s" does not exist.', $name),
        );
    }
}
