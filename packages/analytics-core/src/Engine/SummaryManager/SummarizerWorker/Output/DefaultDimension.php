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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Contracts\Result\Dimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final class DefaultDimension implements Dimension
{
    private ?string $signature = null;

    public function __construct(
        private readonly TranslatableInterface $label,
        private readonly string $name,
        private readonly mixed $member,
        private readonly mixed $rawMember,
        private readonly mixed $displayMember,
        private readonly bool $interpolation,
    ) {}

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
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

    public function isSame(?Dimension $dimension): bool
    {
        if ($dimension === null) {
            return false;
        }

        return $this->name === $dimension->getName()
            && $this->rawMember === $dimension->getRawMember();
    }

    public function isSequence(): bool
    {
        return $this->member instanceof SequenceMember;
    }

    public function getSignature(): string
    {
        if ($this->signature !== null) {
            return $this->signature;
        }

        if (\is_object($this->rawMember)) {
            return $this->signature = hash(
                'xxh128',
                $this->name . ':' . spl_object_id($this->rawMember),
            );
        }

        return $this->signature = hash(
            'xxh128',
            $this->name . ':' . serialize($this->rawMember),
        );
    }

    public function isInterpolation(): bool
    {
        return $this->interpolation;
    }
}
