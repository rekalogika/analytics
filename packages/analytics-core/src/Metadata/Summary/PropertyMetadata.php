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

namespace Rekalogika\Analytics\Metadata\Summary;

use Rekalogika\Analytics\Exception\MetadataException;
use Symfony\Contracts\Translation\TranslatableInterface;

abstract readonly class PropertyMetadata
{
    protected function __construct(
        private string $summaryProperty,
        private TranslatableInterface $label,
        private ?SummaryMetadata $summaryMetadata = null,
    ) {}

    public function getSummaryProperty(): string
    {
        return $this->summaryProperty;
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        if ($this->summaryMetadata === null) {
            throw new MetadataException('Summary table metadata is not set');
        }

        return $this->summaryMetadata;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }
}
