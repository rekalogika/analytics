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

namespace Rekalogika\Analytics\Attribute;

use Rekalogika\Analytics\ValueResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Dimension
{
    /**
     * @param null|string|ValueResolver|array<class-string,string|ValueResolver> $source
     */
    public function __construct(
        private null|string|ValueResolver|array $source = null,
        private null|string|TranslatableInterface $label = null,
        private \DateTimeZone $sourceTimeZone = new \DateTimeZone('UTC'),
        private \DateTimeZone $summaryTimeZone = new \DateTimeZone('UTC'),
    ) {}

    /**
     * @return null|string|ValueResolver|array<class-string,string|ValueResolver>
     */
    public function getSource(): null|string|ValueResolver|array
    {
        return $this->source;
    }

    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }

    public function getSourceTimeZone(): \DateTimeZone
    {
        return $this->sourceTimeZone;
    }

    public function getSummaryTimeZone(): \DateTimeZone
    {
        return $this->summaryTimeZone;
    }
}
