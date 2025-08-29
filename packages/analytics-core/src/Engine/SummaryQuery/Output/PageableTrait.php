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

    /**
     * @return PageInterface<int,object>
     */
    public function getPageByIdentifier(object $pageIdentifier): PageInterface
    {
        return $this->getPageable()->getPageByIdentifier($pageIdentifier);
    }

    /**
     * @return class-string
     */
    public function getPageIdentifierClass(): string
    {
        return $this->getPageable()->getPageIdentifierClass();
    }

    /**
     * @return PageInterface<int,object>
     */
    public function getFirstPage(): PageInterface
    {
        return $this->getPageable()->getFirstPage();
    }

    /**
     * @return null|PageInterface<int,object>
     */
    public function getLastPage(): ?PageInterface
    {
        return $this->getPageable()->getLastPage();
    }

    /**
     * @return \Iterator<PageInterface<int,object>>
     */
    public function getPages(?object $start = null): \Iterator
    {
        return $this->getPageable()->getPages($start);
    }

    /**
     * @return int<1,max>
     */
    public function getItemsPerPage(): int
    {
        return $this->getPageable()->getItemsPerPage();
    }

    /**
     * @param int<1,max> $itemsPerPage
     */
    public function withItemsPerPage(int $itemsPerPage): static
    {
        $new = clone $this;
        $new->pageable = $this->getPageable()->withItemsPerPage($itemsPerPage);

        return $new;
    }

    /**
     * @return null|int<0,max>
     */
    public function getTotalPages(): ?int
    {
        return $this->getPageable()->getTotalPages();
    }

    /**
     * @return null|int<0,max>
     */
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
            ->getCoordinatesQueryComponents($this->getCoordinates());
    }
}
