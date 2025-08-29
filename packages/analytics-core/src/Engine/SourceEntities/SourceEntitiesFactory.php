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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Contracts\SourceEntities;
use Rekalogika\Analytics\Engine\SourceEntities\Query\SourceQuery;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;

final readonly class SourceEntitiesFactory
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
    ) {}

    public function getSourceEntities(Coordinates|Cell $input): SourceEntities
    {
        if ($input instanceof Cell) {
            $input = $input->getCoordinates();
        }

        $summaryClass = $input->getSummaryClass();
        $summaryMetadata = $this->metadataFactory->getSummaryMetadata($summaryClass);
        $entityManager = $this->managerRegistry->getManagerForClass($summaryClass);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new LogicException(\sprintf(
                'The entity manager for class "%s" is not an instance of "EntityManagerInterface".',
                $summaryClass,
            ));
        }

        $sourceQuery = new SourceQuery(
            entityManager: $entityManager,
            summaryMetadata: $summaryMetadata,
        );

        $queryBuilder = $sourceQuery
            ->selectRoot()
            ->fromCoordinates($input)
            ->getQueryBuilder();

        return new DefaultSourceEntities($queryBuilder);
    }

    public function getCoordinatesQueryComponents(Coordinates $coordinates): QueryComponents
    {
        $summaryClass = $coordinates->getSummaryClass();
        $metadata = $this->metadataFactory->getSummaryMetadata($summaryClass);
        $entityManager = $this->managerRegistry->getManagerForClass($summaryClass);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new LogicException(\sprintf(
                'The entity manager for class "%s" is not an instance of "EntityManagerInterface".',
                $summaryClass,
            ));
        }

        $sourceQuery = new SourceQuery(
            entityManager: $entityManager,
            summaryMetadata: $metadata,
        );

        return $sourceQuery
            ->selectMeasures()
            ->fromCoordinates($coordinates)
            ->getQueryComponents();
    }
}
