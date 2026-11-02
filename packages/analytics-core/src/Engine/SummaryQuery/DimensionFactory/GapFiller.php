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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\DatabaseValueAware;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Contracts\Translation\LiteralString;
use Rekalogika\Analytics\Engine\Sequence\SequenceUtil;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class GapFiller
{
    /**
     * Fills gaps in the provided list of dimensions
     *
     * @param list<DefaultDimension> $dimensions
     * @return list<DefaultDimension>
     */
    public static function process(
        array $dimensions,
        DimensionFactory $dimensionFactory,
        DimensionFieldCollection $dimensionFieldCollection,
    ): array {
        $self = new self(
            dimensions: $dimensions,
            dimensionFactory: $dimensionFactory,
            dimensionFieldCollection: $dimensionFieldCollection,
        );

        return $self->getOutput();
    }

    /**
     * @var array<int,DefaultDimension>
     */
    private array $dimensions;

    private TranslatableInterface $label;

    private string $name;

    /**
     * The dimension with the null member, if any. It is not part of the
     * sequence, and is put back at the same end of the output where it was
     * in the input.
     */
    private ?DefaultDimension $nullDimension;

    private bool $isNullDimensionFirst;

    /**
     * @param list<DefaultDimension> $dimensions
     */
    private function __construct(
        array $dimensions,
        private DimensionFactory $dimensionFactory,
        private DimensionFieldCollection $dimensionFieldCollection,
    ) {
        $newDimensions = [];
        $class = null;
        $label = null;
        $name = null;
        $nullDimension = null;
        $isNullDimensionFirst = false;

        foreach ($dimensions as $index => $dimension) {
            $member = $dimension->getMember();

            $label ??= $dimension->getLabel();
            $name ??= $dimension->getName();

            // the null member is not part of the sequence, we keep it aside
            if ($member === null) {
                $nullDimension = $dimension;
                $isNullDimensionFirst = $index === 0;

                continue;
            }

            // ensure member implements SequenceMember
            if (!$member instanceof SequenceMember) {
                throw new InvalidArgumentException(\sprintf(
                    'Dimension must implement "%s".',
                    SequenceMember::class,
                ));
            }

            // ensure member is of the same class
            if ($class === null) {
                $class = $member::class;
            } elseif ($member::class !== $class) {
                throw new InvalidArgumentException(\sprintf(
                    'Dimension must be of the same class "%s".',
                    $class,
                ));
            }

            /** @psalm-suppress MixedAssignment */
            $newDimensions[spl_object_id($member)] = $dimension;
        }

        $this->dimensions = $newDimensions;
        $this->nullDimension = $nullDimension;
        $this->isNullDimensionFirst = $isNullDimensionFirst;

        if ($label === null) {
            $label = new LiteralString('-');
        }

        if ($name === null) {
            $name = '?';
        }

        $this->label = $label;
        $this->name = $name;
    }

    /**
     * @return list<DefaultDimension>
     */
    private function getOutput(): array
    {
        $firstDimension = $this->dimensions[array_key_first($this->dimensions)
            ?? throw new InvalidArgumentException('Dimensions is empty')];

        $lastDimension = $this->dimensions[array_key_last($this->dimensions)
            ?? throw new InvalidArgumentException('Dimensions is empty')];

        $firstMember = $firstDimension->getMember();
        $lastMember = $lastDimension->getMember();

        if (
            !$firstMember instanceof SequenceMember
            || !$lastMember instanceof SequenceMember
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Dimension must implement "%s".',
                SequenceMember::class,
            ));
        }

        $sequence = SequenceUtil::getSequenceFromMembers($firstMember, $lastMember);

        $output = [];

        foreach ($sequence as $current) {
            $output[] = $this->getDimensionFromSequenceMember($current);
        }

        if ($this->nullDimension !== null) {
            if ($this->isNullDimensionFirst) {
                array_unshift($output, $this->nullDimension);
            } else {
                $output[] = $this->nullDimension;
            }
        }

        return $output;
    }

    private function getDimensionFromSequenceMember(
        SequenceMember $member,
    ): DefaultDimension {
        $objectId = spl_object_id($member);

        if (isset($this->dimensions[$objectId])) {
            return $this->dimensions[$objectId];
        }

        // The raw member of a dimension from the query is the value stored in
        // the summary table. An interpolated dimension must have the same, so
        // that it can be serialized and matched like the others.
        /** @psalm-suppress MixedAssignment */
        $rawMember = $member instanceof DatabaseValueAware
            ? $member->getDatabaseValue()
            : $member;

        $dimension = $this->dimensionFactory->createDimension(
            name: $this->name,
            label: $this->label,
            member: $member,
            rawMember: $rawMember,
            displayMember: $member,
            interpolation: true,
        );

        $this->dimensionFieldCollection->collectDimension($dimension);

        return $dimension;
    }
}
