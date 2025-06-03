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

namespace Rekalogika\Analytics\Metadata;

use Rekalogika\Analytics\Metadata\Source\SourceMetadata;

interface SourceMetadataFactory
{
    /**
     * @param class-string $sourceClassName
     */
    public function getSourceMetadata(
        string $sourceClassName,
    ): SourceMetadata;
}
