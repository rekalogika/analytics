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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Contracts\Translation\LiteralString;
use Rekalogika\Analytics\Engine\Sequence\SequenceUtil;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
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
    ): array {
        $self = new self($dimensions, $dimensionFactory);

        return $self->getOutput();
    }

    /**
     * @var array<int,DefaultDimension>
     */
    private array $dimensions;

    private TranslatableInterface $label;

    private string $name;

    /**
     * @param list<DefaultDimension> $dimensions
     */
    private function __construct(
        array $dimensions,
        private DimensionFactory $dimensionFactory,
    ) {
        $newDimensions = [];
        $class = null;
        $label = null;
        $name = null;

        foreach ($dimensions as $dimension) {
            $member = $dimension->getMember();

            $label ??= $dimension->getLabel();
            $name ??= $dimension->getName();

            // @todo we skip null value if there is a null value in the dimensions
            if ($member === null) {
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

        return $output;
    }

    private function getDimensionFromSequenceMember(
        SequenceMember $member,
    ): DefaultDimension {
        $objectId = spl_object_id($member);

        return $this->dimensions[$objectId] ?? $this->dimensionFactory->createDimension(
            name: $this->name,
            label: $this->label,
            member: $member,
            rawMember: $member,
            displayMember: $member,
            interpolation: true,
        );
    }
}
