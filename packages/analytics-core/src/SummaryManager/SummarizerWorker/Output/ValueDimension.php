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

use Rekalogika\Analytics\Query\Dimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class ValueDimension implements Dimension
{
    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private string $summaryClass,
        private string|TranslatableInterface $valuesLabel,
        private mixed $measureLabel,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getLabel(): string|TranslatableInterface
    {
        return $this->valuesLabel;
    }

    #[\Override]
    public function getKey(): string
    {
        return '@values';
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->measureLabel;
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->measureLabel;
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->measureLabel;
    }

}
