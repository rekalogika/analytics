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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;

final class DimensionCollection
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions = [];

    /**
     * @var array<string,array<string,DefaultDimension>>
     */
    private array $uniqueDimensionsByName = [];

    public function collectDimension(DefaultDimension $dimension): void
    {
        $name = $dimension->getName();
        $signature = $dimension->getSignature();

        if (!isset($this->dimensions[$signature])) {
            $this->dimensions[$signature] = $dimension;
        }

        if (!isset($this->uniqueDimensionsByName[$name][$signature])) {
            $this->uniqueDimensionsByName[$name][$signature] = $dimension;
        }
    }

    /**
     * @return list<DefaultDimension>
     */
    public function getDimensionsByName(string $name): array
    {
        $result = $this->uniqueDimensionsByName[$name]
            ?? throw new LogicException(
                \sprintf('No dimensions found for name "%s".', $name),
            );

        return array_values($result);
    }
}
