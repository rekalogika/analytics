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

namespace Rekalogika\Analytics\Engine\SourceEntities;

use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Contracts\SourceEntities;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;

final readonly class DefaultSourceEntities implements SourceEntities
{
    /**
     * @var PageableInterface<array-key,object>
     */
    private PageableInterface $pageable;

    /**
     * @param int<1,max> $itemsPerPage
     */
    public function __construct(
        private QueryBuilder $queryBuilder,
        int $itemsPerPage = 1000,
    ) {
        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
        );

        /** @var KeysetPageable<array-key,object> $pageable */
        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: $itemsPerPage,
        );

        $this->pageable = $pageable;
    }

    #[\Override]
    public function getPageByIdentifier(object $pageIdentifier): PageInterface
    {
        return $this->pageable->getPageByIdentifier($pageIdentifier);
    }

    #[\Override]
    public function getPageIdentifierClass(): string
    {
        return $this->pageable->getPageIdentifierClass();
    }

    #[\Override]
    public function getFirstPage(): PageInterface
    {
        return $this->pageable->getFirstPage();
    }

    // @phpstan-ignore return.unusedType
    #[\Override]
    public function getLastPage(): ?PageInterface
    {
        return $this->pageable->getLastPage();
    }

    #[\Override]
    public function getPages(?object $start = null): \Iterator
    {
        return $this->pageable->getPages($start);
    }

    #[\Override]
    public function getItemsPerPage(): int
    {
        return $this->pageable->getItemsPerPage();
    }

    #[\Override]
    public function withItemsPerPage(int $itemsPerPage): static
    {
        return new self(
            queryBuilder: $this->queryBuilder,
            itemsPerPage: $itemsPerPage,
        );
    }

    #[\Override]
    public function getTotalPages(): ?int
    {
        return $this->pageable->getTotalPages();
    }

    #[\Override]
    public function getTotalItems(): ?int
    {
        return $this->pageable->getTotalItems();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
