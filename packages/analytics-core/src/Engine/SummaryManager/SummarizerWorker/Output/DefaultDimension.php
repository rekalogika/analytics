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
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultDimension implements Dimension
{
    public function __construct(
        private TranslatableInterface $label,
        private string $name,
        private mixed $member,
        private mixed $rawMember,
        private mixed $displayMember,
    ) {}

    public static function createMeasureDimension(
        TranslatableInterface $label,
        MeasureMember $measureMember,
    ): self {
        return new self(
            label: $label,
            name: '@values',
            member: $measureMember,
            rawMember: $measureMember,
            displayMember: $measureMember,
        );
    }

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
        if (\is_object($this->rawMember)) {
            return $this->name . ':' . spl_object_id($this->rawMember);
        }

        return $this->name . ':' . serialize($this->rawMember);
    }
}
