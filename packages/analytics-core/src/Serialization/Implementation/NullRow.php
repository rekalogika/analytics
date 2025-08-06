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

namespace Rekalogika\Analytics\Serialization\Implementation;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\BadMethodCallException;
use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class NullRow implements Row
{
    private NullMeasures $measures;

    private NullTuple $tuple;

    /**
     * @param array<string,mixed> $dimensionMembers
     */
    public function __construct(
        SummaryMetadata $summaryMetadata,
        array $dimensionMembers,
        private ?Expression $condition,
    ) {
        $this->measures = new NullMeasures($summaryMetadata);

        $this->tuple = new NullTuple(
            summaryMetadata: $summaryMetadata,
            dimensionMembers: $dimensionMembers,
            condition: $this->condition,
        );
    }

    #[\Override]
    public function getMeasure(): Measure
    {
        throw new BadMethodCallException('Not yet implemented');
    }

    #[\Override]
    public function getMeasures(): NullMeasures
    {
        return $this->measures;
    }

    #[\Override]
    public function getTuple(): NullTuple
    {
        return $this->tuple;
    }
}
