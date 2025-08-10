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

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;

final class DefaultCell implements CubeCell
{
    use MeasuresTrait;

    /**
     * @var array<string,DefaultCells>
     */
    private array $drillDowns = [];

    /**
     * @param list<string> $measureNames
     */
    public function __construct(
        private readonly DefaultTuple $tuple,
        private readonly DefaultMeasures $measures,
        private readonly array $measureNames,
        private readonly bool $isNull,
        private readonly ResultContext $context,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->tuple->getSummaryClass();
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

    /**
     * @return list<string>
     */
    public function getMeasureNames(): array
    {
        return $this->measureNames;
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->measures;
    }

    #[\Override]
    public function getTuple(): DefaultTuple
    {
        return $this->tuple;
    }

    public function getSignature(): string
    {
        return $this->tuple->getSignature();
    }

    /**
     * @return list<string>
     */
    public function getDimensionality(): array
    {
        return $this->tuple->getDimensionality();
    }

    #[\Override]
    public function rollUp(string $dimensionName): DefaultCell
    {
        $rolledUpTuple = $this->tuple->without($dimensionName);

        return $this->context
            ->getCellRepository()
            ->getCellByTuple($rolledUpTuple)
            ?? throw new UnexpectedValueException('Roll-up cell must always exists.');
    }

    #[\Override]
    public function drillDown(string $dimensionName): DefaultCells
    {
        return $this->drillDowns[$dimensionName] ??= new DefaultCells(
            baseCell: $this,
            childDimensionName: $dimensionName,
            context: $this->context,
        );
    }

    #[\Override]
    public function slice(string $dimensionName, mixed $member): ?CubeCell
    {
        return $this->context
            ->getCellRepository()
            ->getCellsByBaseAndDimension(
                baseCell: $this,
                dimensionName: $dimensionName,
                dimensionMember: $member,
            );
    }

    public function getContext(): ResultContext
    {
        return $this->context;
    }
}
