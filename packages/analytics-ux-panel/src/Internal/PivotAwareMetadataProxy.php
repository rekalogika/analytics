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

namespace Rekalogika\Analytics\UX\PanelBundle\Internal;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class PivotAwareMetadataProxy
{
    /**
     * @var array<string,DimensionMetadata>
     */
    private array $leafDimensions;

    /**
     * @var array<string,DimensionMetadata>
     */
    private array $dimensions;

    /**
     * @var array<string,MeasureMetadata>
     */
    private array $measures;

    public function __construct(
        SummaryMetadata $summaryMetadata,
    ) {
        $this->leafDimensions = array_filter(
            $summaryMetadata->getLeafDimensions(),
            static fn(DimensionMetadata $dimension) => !$dimension->isHidden(),
        );

        $this->dimensions = array_filter(
            $summaryMetadata->getRootDimensions(),
            static fn(DimensionMetadata $dimension) => !$dimension->isHidden(),
        );

        $this->measures = array_filter(
            $summaryMetadata->getMeasures(),
            static fn(MeasureMetadata $measure) => !$measure->isHidden(),
        );
    }

    /**
     * @return array<string,DimensionMetadata>
     */
    public function getLeafDimensions(): array
    {
        return $this->leafDimensions;
    }

    public function getLeafDimension(string $summaryProperty): DimensionMetadata
    {
        return $this->leafDimensions[$summaryProperty]
            ?? throw new InvalidArgumentException(\sprintf(
                'Leaf dimension with summary property "%s" not found.',
                $summaryProperty,
            ));
    }

    /**
     * @return array<string,DimensionMetadata>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    public function getDimension(string $summaryProperty): DimensionMetadata
    {
        return $this->dimensions[$summaryProperty]
            ?? throw new InvalidArgumentException(\sprintf(
                'Dimension with summary property "%s" not found.',
                $summaryProperty,
            ));
    }

    /**
     * @return array<string,MeasureMetadata>
     */
    public function getMeasures(): array
    {
        return $this->measures;
    }
}
