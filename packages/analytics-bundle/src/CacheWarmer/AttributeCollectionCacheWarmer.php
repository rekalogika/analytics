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

namespace Rekalogika\Analytics\Bundle\CacheWarmer;

use Rekalogika\Analytics\Contracts\Exception\SummaryNotFound;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Pre-populates the cache used by CachingAttributeCollectionFactory
 * (cache.system by default — see config/metadata.php) during cache:warmup,
 * so the first request after deploy doesn't pay the reflection cost of
 * parsing every #[Summary] / #[Dimension] / #[Measure] attribute.
 *
 * Walks every Doctrine-managed entity that carries #[Summary] and resolves
 * its full SummaryMetadata. Internally that calls
 *   AttributeCollectionFactory::getClassAttributes($class)
 *   AttributeCollectionFactory::getPropertyAttributes($class, $property)
 * for every relevant class and property, which the caching decorator
 * persists.
 *
 * isOptional() returns true so a warmup-time Doctrine boot failure (rare
 * but possible in environments without DB connectivity at compile time)
 * never blocks deploy; the cache then falls back to lazy population on
 * first request, matching the pre-warmer behaviour of the bundle.
 */
final readonly class AttributeCollectionCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->summaryMetadataFactory->getSummaryClasses() as $summaryClass) {
            try {
                $this->summaryMetadataFactory->getSummaryMetadata($summaryClass);
            } catch (SummaryNotFound) {
                // Defensive: getSummaryClasses() already filters, but a race
                // between metadata layers shouldn't fail the whole warmup.
            }
        }

        return [];
    }
}
