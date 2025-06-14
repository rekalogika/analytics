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

use Rekalogika\Analytics\Contracts\Model\Bin;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Core\Util\LiteralString;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
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
            if (!$member instanceof Bin) {
                throw new InvalidArgumentException(\sprintf(
                    'Dimension must implement "%s".',
                    Bin::class,
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
        $firstRawMember = $firstDimension->getRawMember();
        $lastRawMember = $lastDimension->getRawMember();

        if (
            !$firstRawMember instanceof Bin
            || !$lastRawMember instanceof Bin
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Dimension must implement "%s".',
                Bin::class,
            ));
        }

        $sequence = $this->getSequence($firstRawMember, $lastRawMember);

        foreach ($sequence as $current) {
            yield $this->getDimensionFromSequenceMember($current);
        }
    }

    /**
     * @param Bin<mixed> $member
     */
    private function getDimensionFromSequenceMember(
        Bin $member,
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
     * @template T
     * @param Bin<T> $first
     * @param Bin<T> $last
     * @return iterable<Bin<T>>
     */
    private function getSequence(
        Bin $first,
        Bin $last,
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
            while ($current instanceof Bin) {
                yield $current;

                if ($current === $last) {
                    break;
                }

                $current = $current->getNext();
            }
        } else { // descending
            while ($current instanceof Bin) {
                yield $current;

                if ($current === $last) {
                    break;
                }

                $current = $current->getPrevious();
            }
        }
    }
}
