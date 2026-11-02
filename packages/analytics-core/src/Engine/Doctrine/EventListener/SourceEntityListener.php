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

namespace Rekalogika\Analytics\Engine\Doctrine\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\DirtySummaryEvent;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\NewDirtyFlagEvent;
use Symfony\Contracts\Service\ResetInterface;

final class SourceEntityListener implements ResetInterface
{
    /**
     * @var array<string,DirtyFlag>
     */
    private array $dirtyFlags = [];

    /**
     * Entities inserted in the current flush, keyed by object id.
     *
     * @var array<int,object>
     */
    private array $insertedEntities = [];

    public function __construct(
        private readonly HandlerFactory $handlerFactory,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    #[\Override]
    public function reset(): void
    {
        $this->dirtyFlags = [];
        $this->insertedEntities = [];
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        // process deletions

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $newDirtyFlags = $this->handlerFactory
                ->getSource($entity)
                ->generateDirtyFlagsForEntityDeletion($entity);

            foreach ($newDirtyFlags as $dirtyFlag) {
                $this->dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
            }
        }

        // process updates

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $changedProperties = array_keys($unitOfWork->getEntityChangeSet($entity));

            $newDirtyFlags = $this->handlerFactory
                ->getSource($entity)
                ->generateDirtyFlagsForEntityModification(
                    entity: $entity,
                    modifiedProperties: $changedProperties,
                );

            foreach ($newDirtyFlags as $dirtyFlag) {
                $this->dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
            }
        }

        // process inserts

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $newDirtyFlags = $this->handlerFactory
                ->getSource($entity)
                ->generateDirtyFlagsForEntityCreation($entity);

            foreach ($newDirtyFlags as $dirtyFlag) {
                $this->dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
            }

            // the partition flags are created in postPersist, after the key
            // is generated

            $this->insertedEntities[spl_object_id($entity)] = $entity;
        }

        // process collection deletions

        foreach ($unitOfWork->getScheduledCollectionDeletions() as $collection) {
            // iterate of the collection, find the backRef to the owner. use the
            // relation as the 'changedProperties' argument

            // @todo doctrine internal
            $mapping = $collection->getMapping();
            $backRefFieldName = $mapping['mappedBy'];

            if (!\is_string($backRefFieldName)) {
                continue;
            }

            foreach ($collection as $entity) {
                $newDirtyFlags = $this->handlerFactory
                    ->getSource($entity)
                    ->generateDirtyFlagsForEntityModification(
                        entity: $entity,
                        modifiedProperties: [$backRefFieldName],
                    );

                foreach ($newDirtyFlags as $dirtyFlag) {
                    $this->dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
                }
            }
        }

        // process collection updates

        // not necessary? because we only consider manyToOne and it is already
        // handled in the entity updates
        // foreach ($unitOfWork->getScheduledCollectionUpdates() as $collection) {
        // }

        // persist and compute changesets

        $classMetadata = $entityManager->getClassMetadata(DirtyFlag::class);

        foreach ($this->dirtyFlags as $dirtyFlag) {
            $entityManager->persist($dirtyFlag);
            $unitOfWork->computeChangeSet($classMetadata, $dirtyFlag);
        }
    }

    /**
     * Creates the dirty flags of the partitions containing the inserted
     * entity. postPersist runs after the insert, so a key generated by the
     * database (e.g. an identity column) is known, and before the commit, so
     * the flags are committed in the same transaction as the entity. This
     * covers an entity whose transaction commits after a refresh has moved
     * the latest summarized key past its key.
     *
     * The insert queries have already been executed at this point, so the
     * flags are inserted using DBAL.
     */
    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();
        $objectId = spl_object_id($entity);

        if (!isset($this->insertedEntities[$objectId])) {
            return;
        }

        unset($this->insertedEntities[$objectId]);

        $newDirtyFlags = $this->handlerFactory
            ->getSource($entity)
            ->generatePartitionDirtyFlagsForEntityCreation($entity);

        foreach ($newDirtyFlags as $dirtyFlag) {
            $signature = $dirtyFlag->getSignature();

            // already inserted in this flush

            if (isset($this->dirtyFlags[$signature])) {
                continue;
            }

            $this->insertDirtyFlag($event->getObjectManager(), $dirtyFlag);
            $this->dirtyFlags[$signature] = $dirtyFlag;
        }
    }

    private function insertDirtyFlag(
        EntityManagerInterface $entityManager,
        DirtyFlag $dirtyFlag,
    ): void {
        $classMetadata = $entityManager->getClassMetadata(DirtyFlag::class);
        $idGenerator = $classMetadata->idGenerator;
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $quoteStrategy = $entityManager->getConfiguration()->getQuoteStrategy();

        /** @var array<string,mixed> */
        $fields = [
            'class' => $dirtyFlag->getClass(),
            'level' => $dirtyFlag->getLevel(),
            'key' => $dirtyFlag->getKey(),
            'created' => $dirtyFlag->getCreated(),
        ];

        // identity columns are generated by the database, other generators
        // (e.g. sequences) are invoked here

        if (!$idGenerator->isPostInsertGenerator()) {
            /** @psalm-suppress MixedAssignment */
            $fields['id'] = $idGenerator->generateId($entityManager, $dirtyFlag);
        }

        $data = [];
        $types = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($fields as $field => $value) {
            $column = $quoteStrategy->getColumnName($field, $classMetadata, $platform);
            $data[$column] = $value;
            $types[$column] = $classMetadata->getTypeOfField($field) ?? 'string';
        }

        $connection->insert(
            $quoteStrategy->getTableName($classMetadata, $platform),
            $data,
            $types,
        );
    }

    /**
     * Dispatch dirty flags in postFlush because the flush might fail and we
     * don't want to dispatch the event if the flush fails.
     */
    public function postFlush(PostFlushEventArgs $doctrineEvent): void
    {
        $summaryClassses = [];

        foreach ($this->dirtyFlags as $dirtyFlag) {
            $event = new NewDirtyFlagEvent($dirtyFlag);
            $summaryClassses[$dirtyFlag->getClass()] = true;
            $this->eventDispatcher?->dispatch($event);
        }

        foreach (array_keys($summaryClassses) as $summaryClass) {
            $event = new DirtySummaryEvent($summaryClass);
            $this->eventDispatcher?->dispatch($event);
        }

        $this->dirtyFlags = [];
        $this->insertedEntities = [];
    }
}
