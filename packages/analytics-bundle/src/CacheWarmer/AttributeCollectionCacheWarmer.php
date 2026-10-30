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
 * Failures are caught so a warmup-time Doctrine boot failure (rare but
 * possible in environments without DB connectivity at compile time) never
 * blocks deploy; the cache then falls back to lazy population on first
 * request, matching the pre-warmer behaviour of the bundle. Symfony does not
 * catch exceptions thrown by optional warmers, isOptional() only lets them be
 * skipped with --no-optional-warmers.
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
        try {
            $summaryClasses = $this->summaryMetadataFactory->getSummaryClasses();
        } catch (\Throwable) {
            return [];
        }

        foreach ($summaryClasses as $summaryClass) {
            try {
                $this->summaryMetadataFactory->getSummaryMetadata($summaryClass);
            } catch (\Throwable) {
                // Invalid metadata shouldn't fail the whole warmup, the error
                // will surface when the summary is used.
            }
        }

        return [];
    }
}
