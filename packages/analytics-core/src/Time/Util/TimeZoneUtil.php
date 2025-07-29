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

namespace Rekalogika\Analytics\Time\Util;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\PropertyMetadata;
use Rekalogika\Analytics\Time\Metadata\TimeProperties;

final readonly class TimeZoneUtil
{
    private function __construct() {}

    /**
     * @return list{\DateTimeZone,\DateTimeZone}
     */
    public static function resolveTimeZones(
        PropertyMetadata $propertyMetadata,
    ): array {
        if ($propertyMetadata instanceof DimensionMetadata) {
            $dimensionMetadata = $propertyMetadata;
        } else {
            throw new InvalidArgumentException(\sprintf(
                'Unknown property metadata type: %s',
                $propertyMetadata::class,
            ));
        }

        $timeProperties = $dimensionMetadata
            ->getAttributes()
            ->tryGetAttribute(TimeProperties::class);

        if ($timeProperties instanceof TimeProperties) {
            return [
                $timeProperties->getSourceTimeZone(),
                $timeProperties->getSummaryTimeZone(),
            ];
        }

        return [
            new \DateTimeZone('UTC'),
            new \DateTimeZone('UTC'),
        ];
    }
}
