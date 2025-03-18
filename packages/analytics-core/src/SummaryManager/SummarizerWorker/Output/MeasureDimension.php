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

use Rekalogika\Analytics\Contracts\Dimension;
use Rekalogika\Analytics\Contracts\MeasureMember;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class MeasureDimension implements Dimension
{
    public function __construct(
        private TranslatableInterface $label,
        private MeasureMember $measureMember,
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
    public function getMember(): MeasureMember
    {
        return $this->measureMember;
    }

    #[\Override]
    public function getRawMember(): MeasureMember
    {
        return $this->measureMember;
    }

    #[\Override]
    public function getDisplayMember(): MeasureMember
    {
        return $this->measureMember;
    }
}
