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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\GapFiller;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Engine\Util\DimensionUtil;

/**
 * Get unique dimensions while preserving the order of the dimensions.
 */
final class DimensionFieldCollection
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $collected = [];

    /**
     * @var list<DefaultDimension>|null
     */
    private ?array $sorted = null;

    /**
     * @var list<DefaultDimension>|null
     */
    private ?array $gapFilled = null;

    public function __construct(
        private readonly string $name,
        private readonly ?Order $order,
        private readonly DimensionFactory $dimensionFactory,
    ) {}

    private function reset(): void
    {
        $this->sorted = null;
        $this->gapFilled = null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function collectDimension(
        DefaultDimension $dimension,
    ): void {
        $signature = $dimension->getSignature();

        if (isset($this->collected[$signature])) {
            return;
        }

        $this->collected[$signature] = $dimension;
        $this->reset();
    }

    /**
     * @return list<DefaultDimension>
     */
    public function getSorted(): array
    {
        if ($this->sorted !== null) {
            return $this->sorted;
        }

        if ($this->order === null) {
            return $this->sorted = array_values($this->collected);
        }

        return $this->sorted = array_values(DimensionUtil::sort(
            dimensions: array_values($this->collected),
            order: $this->order,
        ));
    }

    /**
     * @return list<DefaultDimension>
     */
    public function getGapFilled(): array
    {
        if ($this->gapFilled !== null) {
            return $this->gapFilled;
        }

        $sorted = $this->getSorted();

        if ($sorted === []) {
            return $this->gapFilled = [];
        }

        $filled = $this->tryFillGaps($sorted);

        return $this->gapFilled = $filled;
    }

    /**
     * @return list<DefaultDimension>
     */
    public function getResult(): array
    {
        $dimensions = $this->collected;

        if ($this->order instanceof Order) {
            $dimensions = DimensionUtil::sort(
                dimensions: array_values($dimensions),
                order: $this->order,
            );
        }

        // Fill gaps if the first member is a sequence member
        $dimensions = $this->tryFillGaps(array_values($dimensions));

        return $dimensions;
    }

    /**
     * @param list<DefaultDimension> $dimensions
     * @return list<DefaultDimension>
     */
    private function tryFillGaps(array $dimensions): array
    {
        $firstMember = $dimensions[0]->getMember();

        if (!$firstMember instanceof SequenceMember) {
            return $dimensions;
        }

        return GapFiller::process($dimensions, $this->dimensionFactory);
    }
}
