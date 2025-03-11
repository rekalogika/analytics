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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Query\Tuple;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultTuple;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultTuple implements Tuple
{
    /**
     * @param array<string,TranslatableInterface|string> $labels
     * @param array<string,mixed> $members
     */
    public function __construct(
        private array $labels,
        private array $members,
    ) {}


    public static function fromResultTuple(ResultTuple $resultTuple): self
    {
        $labels = array_map(
            static fn(ResultValue $value): TranslatableInterface|string => $value->getLabel(),
            $resultTuple->getDimensions(),
        );

        $members = array_map(
            static fn(ResultValue $value): mixed => $value->getValue(),
            $resultTuple->getDimensions(),
        );

        return new self(
            labels: $labels,
            members: $members,
        );
    }

    #[\Override]
    public function getLabels(): array
    {
        return $this->labels;
    }

    #[\Override]
    public function getMembers(): array
    {
        return $this->members;
    }
}
