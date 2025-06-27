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

namespace Rekalogika\PivotTable\Implementation\Table;

use Rekalogika\PivotTable\Table\Table;
use Rekalogika\PivotTable\Table\TableVisitor;

/**
 * @implements \IteratorAggregate<DefaultTableSection>
 */
final readonly class DefaultTable implements \Countable, Table, \IteratorAggregate
{
    /**
     * @var list<DefaultTableSection>
     */
    private array $rowGroups;

    private DefaultRows $rows;

    /**
     * @param iterable<DefaultTableSection> $rowGroups
     */
    public function __construct(
        iterable $rowGroups,
    ) {
        $newRowGroups = [];
        $newRows = [];

        foreach ($rowGroups as $rowGroup) {
            $newRowGroups[] = $rowGroup;

            foreach ($rowGroup as $row) {
                $newRows[] = $row;
            }
        }

        $this->rowGroups = $newRowGroups;
        $this->rows = new DefaultRows($newRows);
    }

    #[\Override]
    public function accept(TableVisitor $visitor): mixed
    {
        return $visitor->visitTable($this);
    }

    #[\Override]
    public function getTagName(): string
    {
        return 'table';
    }

    #[\Override]
    public function getRows(): DefaultRows
    {
        return $this->rows;
    }

    /**
     * @return \Traversable<DefaultTableSection>
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->rowGroups);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->rowGroups);
    }
}
