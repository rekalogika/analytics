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

final readonly class MeasureDimension implements Dimension
{
    public function __construct(
        private TranslatableInterface $label,
        private mixed $measure,
    ) {}

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getKey(): string
    {
        return '@values';
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->measure;
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->measure;
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->measure;
    }
}
