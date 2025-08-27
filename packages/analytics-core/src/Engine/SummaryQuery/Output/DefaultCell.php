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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\BadMethodCallException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;

final class DefaultCell implements CubeCell
{
    use MeasuresTrait;

    /**
     * @var array<string,DefaultCells>
     */
    private array $drillDowns = [];

    public function __construct(
        private readonly DefaultCoordinates $coordinates,
        private readonly DefaultMeasures $measures,
        private readonly bool $isNull,
        private readonly ResultContext $context,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->coordinates->getSummaryClass();
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->isNull;
    }

    #[\Override]
    public function getApex(): self
    {
        return $this->context->getCellRepository()->getApexCell();
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->measures;
    }

    #[\Override]
    public function getCoordinates(): DefaultCoordinates
    {
        return $this->coordinates;
    }

    public function getMember(string $dimension): mixed
    {
        return $this->coordinates->get($dimension)?->getMember();
    }

    public function getSignature(): string
    {
        return $this->coordinates->getSignature();
    }

    /**
     * @return list<string>
     */
    public function getDimensionality(): array
    {
        return $this->coordinates->getDimensionality();
    }

    #[\Override]
    public function rollUp(string $dimension): DefaultCell
    {
        $rolledUpCoordinates = $this->coordinates->without($dimension);

        return $this->context
            ->getCellRepository()
            ->getCellByCoordinates($rolledUpCoordinates);
    }

    #[\Override]
    public function drillDown(string $dimension): DefaultCells
    {
        return $this->drillDowns[$dimension] ??= new DefaultCells(
            baseCell: $this,
            childDimensionName: $dimension,
            context: $this->context,
        );
    }

    #[\Override]
    public function slice(string $dimension, mixed $member): CubeCell
    {
        return $this->context
            ->getCellRepository()
            ->getCellsByBaseAndDimension(
                baseCell: $this,
                dimensionName: $dimension,
                dimensionMember: $member,
            );
    }

    #[\Override]
    public function find(string $dimension, mixed $argument): ?CubeCell
    {
        $slices = $this->drillDown($dimension);

        foreach ($slices as $cell) {
            /** @psalm-suppress MixedAssignment */
            $member = $cell->getMember($dimension);

            if ($member === $argument) {
                return $cell;
            } elseif ($member instanceof MeasureMember) {
                if ($member->getMeasureProperty() === $argument) {
                    return $cell;
                }
            } elseif ($member instanceof \Stringable) {
                if ($member->__toString() === $argument) {
                    return $cell;
                }
            } elseif (\is_callable($argument)) {
                if ($argument($member) === true) {
                    return $cell;
                }
            }
        }

        return null;
    }

    #[\Override]
    public function dice(?Expression $predicate): DefaultCell
    {
        throw new BadMethodCallException('dice() is not yet implemented.');
    }

    public function getContext(): ResultContext
    {
        return $this->context;
    }
}
