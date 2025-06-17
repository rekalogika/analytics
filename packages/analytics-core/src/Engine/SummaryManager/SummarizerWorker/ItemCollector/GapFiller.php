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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Common\Model\LiteralString;
use Rekalogika\Analytics\Contracts\Model\Sequence;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class GapFiller
{
    /**
     * @var array<int,DefaultDimension>
     */
    private array $dimensions;

    private TranslatableInterface $label;

    private string $name;

    /**
     * @param non-empty-list<DefaultDimension> $dimensions
     */
    private function __construct(
        array $dimensions,
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

            // ensure member implements Bin
            if (!$member instanceof Sequence) {
                throw new InvalidArgumentException(\sprintf(
                    'Dimension must implement "%s".',
                    Sequence::class,
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
        $firstMember = $firstDimension->getMember();
        $lastMember = $lastDimension->getMember();

        if (
            !$firstMember instanceof Sequence
            || !$lastMember instanceof Sequence
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Dimension must implement "%s".',
                Sequence::class,
            ));
        }

        $sequence = $this->getSequence($firstMember, $lastMember);

        foreach ($sequence as $current) {
            yield $this->getDimensionFromSequenceMember($current);
        }
    }

    private function getDimensionFromSequenceMember(
        Sequence $member,
    ): DefaultDimension {
        $objectId = spl_object_id($member);

        return $this->dimensions[$objectId] ?? new DefaultDimension(
            label: $this->label,
            name: $this->name,
            member: $member,
            rawMember: $member,
            displayMember: $member,
        );
    }

    /**
     * @template T of Sequence
     * @param T $first
     * @param T $last
     * @return iterable<T>
     */
    private function getSequence(
        Sequence $first,
        Sequence $last,
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
            while ($current instanceof Sequence) {
                yield $current;

                if ($current === $last) {
                    break;
                }

                $current = $current->getNext();
            }
        } else { // descending
            while ($current instanceof Sequence) {
                yield $current;

                if ($current === $last) {
                    break;
                }

                $current = $current->getPrevious();
            }
        }
    }
}
