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
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultDimension implements Dimension
{
    public function __construct(
        private string|TranslatableInterface $label,
        private string $key,
        private mixed $member,
        private mixed $rawMember,
    ) {}

    public static function createFromResultValue(ResultValue $resultValue): self
    {
        return new self(
            label: $resultValue->getLabel(),
            key: $resultValue->getField(),
            member: $resultValue->getValue(),
            rawMember: $resultValue->getRawValue(),
        );
    }

    #[\Override]
    public function getLabel(): string|TranslatableInterface
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
}
