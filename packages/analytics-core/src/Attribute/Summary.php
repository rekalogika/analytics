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

use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Summary
{
    /**
     * @var non-empty-list<class-string>
     */
    private array $sourceClasses;

    /**
     * @param class-string|non-empty-list<class-string> $sourceClass
     */
    public function __construct(
        string|array $sourceClass,
        private null|string|TranslatableInterface $label = null,
    ) {
        if (\is_string($sourceClass)) {
            $this->sourceClasses = [$sourceClass];
        } else {
            $this->sourceClasses = $sourceClass;
        }
    }

    /**
     * @return non-empty-list<class-string>
     */
    public function getSourceClasses(): array
    {
        return $this->sourceClasses;
    }

    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }
}
