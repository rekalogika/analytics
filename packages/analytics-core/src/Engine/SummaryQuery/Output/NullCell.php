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
use Rekalogika\Contracts\Rekapager\PageableInterface;

final class NullCell implements CubeCell
{
    use MeasuresTrait;

    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private readonly string $summaryClass,
    ) {}

    #[\Override]
    public function getSourceEntities(): ?PageableInterface
    {
        return null;
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function isNull(): bool
    {
        return true;
    }

    #[\Override]
    public function getApex(): self
    {
        return $this;
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return new DefaultMeasures([]);
    }

    #[\Override]
    public function getCoordinates(): DefaultCoordinates
    {
        return new DefaultCoordinates(
            summaryClass: $this->summaryClass,
            dimensions: [],
            condition: null,
        );
    }

    /**
     * @return list<string>
     */
    public function getDimensionality(): array
    {
        return [];
    }

    #[\Override]
    public function rollUp(string $dimension): self
    {
        return $this;
    }

    #[\Override]
    public function drillDown(string $dimension): NullCells
    {
        return new NullCells();
    }

    #[\Override]
    public function slice(string $dimension, mixed $member): CubeCell
    {
        return $this;
    }

    #[\Override]
    public function find(string $dimension, mixed $argument): CubeCell
    {
        return $this;
    }

    #[\Override]
    public function dice(?Expression $predicate): CubeCell
    {
        throw new BadMethodCallException('dice() is not yet implemented.');
    }
}
