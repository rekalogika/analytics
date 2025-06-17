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

namespace Rekalogika\Analytics\Engine\SummaryManager\Event;

use Rekalogika\Analytics\Engine\SummaryManager\PartitionRange;
use Rekalogika\Analytics\Engine\Util\DateTimeUtil;

abstract readonly class AbstractEndEvent implements \Stringable
{
    private float $end;

    /**
     * @param class-string $class
     */
    final public function __construct(
        private string $class,
        private PartitionRange $range,
        private float $start,
    ) {
        $this->end = microtime(true);
    }

    abstract public function getEventId(): string;

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getRange(): PartitionRange
    {
        return $this->range;
    }

    public function getStart(): \DateTimeInterface
    {
        return DateTimeUtil::floatToDateTime($this->start);
    }

    public function getEnd(): \DateTimeInterface
    {
        return DateTimeUtil::floatToDateTime($this->end);
    }

    public function getDuration(): \DateInterval
    {
        return $this->getStart()->diff($this->getEnd());
    }
}
