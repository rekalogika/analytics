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

use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;

trait PageableTrait
{
    /**
     * @var PageableInterface<int,object>|null
     */
    private ?PageableInterface $pageable = null;

    abstract private function getSourceEntitiesFactory(): SourceEntitiesFactory;

    abstract private function getCoordinates(): Coordinates;

    /**
     * @return PageableInterface<int,object>
     */
    private function getPageable(): PageableInterface
    {
        return $this->pageable
            ??= $this->getSourceEntitiesFactory()
            ->getSourceEntities($this->getCoordinates());
    }

    #[\Override]
    public function getPageByIdentifier(object $pageIdentifier): PageInterface
    {
        return $this->getPageable()->getPageByIdentifier($pageIdentifier);
    }

    #[\Override]
    public function getPageIdentifierClass(): string
    {
        return $this->getPageable()->getPageIdentifierClass();
    }

    #[\Override]
    public function getFirstPage(): PageInterface
    {
        return $this->getPageable()->getFirstPage();
    }

    #[\Override]
    public function getLastPage(): ?PageInterface
    {
        return $this->getPageable()->getLastPage();
    }

    #[\Override]
    public function getPages(?object $start = null): \Iterator
    {
        return $this->getPageable()->getPages($start);
    }

    #[\Override]
    public function getItemsPerPage(): int
    {
        return $this->getPageable()->getItemsPerPage();
    }

    #[\Override]
    public function withItemsPerPage(int $itemsPerPage): static
    {
        $new = clone $this;
        $new->pageable = $this->getPageable()->withItemsPerPage($itemsPerPage);

        return $new;
    }

    #[\Override]
    public function getTotalPages(): ?int
    {
        return $this->getPageable()->getTotalPages();
    }

    #[\Override]
    public function getTotalItems(): ?int
    {
        return $this->getPageable()->getTotalItems();
    }

    //
    // querycomponents
    //

    public function getSourceQueryComponents(): QueryComponents
    {
        return $this->sourceEntitiesFactory
            ->getCoordinatesQueryComponents($this->coordinates);
    }
}
