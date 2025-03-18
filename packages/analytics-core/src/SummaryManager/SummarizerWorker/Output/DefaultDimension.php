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
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultDimension implements Dimension
{
    public function __construct(
        private TranslatableInterface $label,
        private string $key,
        private mixed $member,
        private mixed $rawMember,
        private mixed $displayMember,
    ) {}

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->key;
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->member;
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->rawMember;
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->displayMember;
    }
}
