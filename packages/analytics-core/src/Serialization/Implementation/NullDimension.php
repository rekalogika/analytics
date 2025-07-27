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

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class NullDimension implements Dimension
{
    private TranslatableInterface $label;

    public function __construct(
        private string $name,
        private mixed $member,
        SummaryMetadata $summaryMetadata,
    ) {
        $dimension = $summaryMetadata->getDimension($name);
        $this->label = $dimension->getLabel();
    }

    /**
     * @todo does not consider getters yet
     */
    #[\Override]
    public function getMember(): mixed
    {
        return $this->member;
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->member;
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->member;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }
}
