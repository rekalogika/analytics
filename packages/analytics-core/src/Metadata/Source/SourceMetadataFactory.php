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

namespace Rekalogika\Analytics\Metadata\Source;

/**
 * Provides the metadata of source entity classes, i.e. the entities being
 * summarized. The inverse of `SummaryMetadataFactory`: given a source, which
 * summaries depend on it.
 */
interface SourceMetadataFactory
{
    /**
     * Returns the metadata of the source class. Every class is a valid
     * source. A class that no summary depends on gets empty metadata.
     *
     * @param class-string $sourceClassName
     */
    public function getSourceMetadata(
        string $sourceClassName,
    ): SourceMetadata;
}
