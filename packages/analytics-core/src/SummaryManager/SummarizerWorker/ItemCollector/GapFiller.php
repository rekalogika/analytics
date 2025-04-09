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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector;

use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class GapFiller
{
    /**
     * Object id of raw member to dimension
     *
     * @var array<int,DefaultDimension>
     */
    private array $dimensions;

    private TranslatableInterface $label;
    private string $key;

    /**
     * @param non-empty-list<DefaultDimension> $dimensions
     */
    private function __construct(
        array $dimensions,
    ) {
        $newDimensions = [];
        $class = null;
        $label = null;
        $key = null;

        foreach ($dimensions as $dimension) {
            $rawMember = $dimension->getRawMember();

            $label ??= $dimension->getLabel();
            $key ??= $dimension->getKey();

            // @todo we skip null value if there is a null value in the dimensions
            if ($rawMember === null) {
                continue;
            }

            // ensure member implements SequenceMember
            if (!$rawMember instanceof SequenceMember) {
                throw new InvalidArgumentException(\sprintf(
                    'Dimension must implement "%s".',
                    SequenceMember::class,
                ));
            }

            // ensure member is of the same class
            if ($class === null) {
                $class = $rawMember::class;
            } elseif ($rawMember::class !== $class) {
                throw new InvalidArgumentException(\sprintf(
                    'Dimension must be of the same class "%s".',
                    $class,
                ));
            }

            /** @psalm-suppress MixedAssignment */
            $newDimensions[spl_object_id($rawMember)] = $dimension;
        }

        $this->dimensions = $newDimensions;
        $this->label = $label;
        $this->key = $key;
    }

    /**
     * @param non-empty-list<DefaultDimension> $dimensions
     * @return non-empty-list<DefaultDimension>
     */
    public static function process(array $dimensions): array
    {
        $self = new self($dimensions);

        /**
         * @var non-empty-list<DefaultDimension>
         * @psalm-suppress InvalidArgument
         */
        return array_values(iterator_to_array($self->getOutput()));
    }

    /**
     * @return iterable<DefaultDimension>
     */
    private function getOutput(): iterable
    {
        $firstDimension = $this->dimensions[array_key_first($this->dimensions) ?? throw new InvalidArgumentException('Dimensions is empty')];
        $lastDimension = $this->dimensions[array_key_last($this->dimensions) ?? throw new InvalidArgumentException('Dimensions is empty')];
        $firstRawMember = $firstDimension->getRawMember();
        $lastRawMember = $lastDimension->getRawMember();

        if (
            !$firstRawMember instanceof SequenceMember
            || !$lastRawMember instanceof SequenceMember
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Dimension must implement "%s".',
                SequenceMember::class,
            ));
        }

        $sequence = $this->getSequence($firstRawMember, $lastRawMember);

        foreach ($sequence as $current) {
            yield $this->getDimensionFromSequenceMember($current);
        }
    }

    private function getDimensionFromSequenceMember(
        SequenceMember $member,
    ): DefaultDimension {
        $objectId = spl_object_id($member);

        if (isset($this->dimensions[$objectId])) {
            return $this->dimensions[$objectId];
        }

        return new DefaultDimension(
            label: $this->label,
            key: $this->key,
            member: $member,
            rawMember: $member,
            displayMember: $member,
        );
    }

    /**
     * @return iterable<SequenceMember>
     */
    private function getSequence(
        SequenceMember $first,
        SequenceMember $last,
    ): iterable {
        $class = $first::class;

        if ($class !== $last::class) {
            throw new InvalidArgumentException(\sprintf(
                'Sequence member must be of the same class "%s".',
                $class,
            ));
        }

        $comparison = $class::compare($first, $last);
        $current = $first;

        if ($comparison === 0) {
            yield $first;
        } elseif ($class::compare($first, $last) < 0) { // ascending
            while ($current !== null) {
                yield $current;

                if ($current === $last) {
                    break;
                }

                $current = $current->getNext();
            }
        } else { // descending
            while ($current !== null) {
                yield $current;

                if ($current === $last) {
                    break;
                }

                $current = $current->getPrevious();
            }
        }
    }
}
