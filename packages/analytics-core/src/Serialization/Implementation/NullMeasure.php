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

use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\Contracts\Result\Unit;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class NullMeasure implements Measure
{
    private TranslatableInterface $label;

    public function __construct(
        private string $name,
        SummaryMetadata $summaryMetadata,
    ) {
        $measure = $summaryMetadata->getMeasure($name);
        $this->label = $measure->getLabel();
    }

    #[\Override]
    public function getValue(): mixed
    {
        return null;
    }

    #[\Override]
    public function getRawValue(): mixed
    {
        return null;
    }

    #[\Override]
    public function getUnit(): ?Unit
    {
        return null;
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
