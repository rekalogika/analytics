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

namespace Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultDimension;
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
     * @var array<string,DefaultDimension>
     */
    private array $byMember = [];


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

        $memberSignature = DefaultDimension::getMemberSignature($dimension->getRawMember());

        $this->collected[$signature] = $dimension;
        $this->byMember[$memberSignature] = $dimension;
        $this->reset();
    }

    public function getDimensionByMember(mixed $member): ?DefaultDimension
    {
        $memberSignature = DefaultDimension::getMemberSignature($member);

        // An interpolated member is only collected when the gaps are filled.
        // Fill them so that the member can be found as in the drill-down.
        if (!isset($this->byMember[$memberSignature])) {
            $this->getGapFilled();
        }

        return $this->byMember[$memberSignature] ?? null;
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
     * @param list<DefaultDimension> $dimensions
     * @return list<DefaultDimension>
     */
    private function tryFillGaps(array $dimensions): array
    {
        // Gap filling requires the members to be sorted by their value. If the
        // order is null, we keep the database order, which might be determined
        // by a measure.
        if ($this->order === null) {
            return $dimensions;
        }

        // The null member sorts first in descending order, so we look for the
        // first non-null member.
        $firstMember = null;

        foreach ($dimensions as $dimension) {
            /** @psalm-suppress MixedAssignment */
            $firstMember = $dimension->getMember();

            if ($firstMember !== null) {
                break;
            }
        }

        if (!$firstMember instanceof SequenceMember) {
            return $dimensions;
        }

        return GapFiller::process(
            dimensions: $dimensions,
            dimensionFactory: $this->dimensionFactory,
            dimensionFieldCollection: $this,
        );
    }
}
