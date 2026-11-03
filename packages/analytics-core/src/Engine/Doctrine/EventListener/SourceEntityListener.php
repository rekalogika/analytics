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
use Rekalogika\Analytics\Metadata\Source\AssociationDependency;
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

    /**
     * Related entities modified or deleted in the current flush, grouped by
     * the summary and the path from its source entities. The entities are
     * keyed by object id.
     *
     * @var array<string,array{dependency:AssociationDependency,entities:array<int,object>}>
     */
    private array $referencedEntities = [];

    public function __construct(
        private readonly HandlerFactory $handlerFactory,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    #[\Override]
    public function reset(): void
    {
        $this->dirtyFlags = [];
        $this->insertedEntities = [];
        $this->referencedEntities = [];
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        // clear the state left by a previous flush that failed, which never
        // reached postFlush(). its dirty flags were rolled back with it

        $this->reset();

        $unitOfWork = $entityManager->getUnitOfWork();

        // process deletions

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $sourceHandler = $this->handlerFactory->getSource($entity);
            $newDirtyFlags = $sourceHandler->generateDirtyFlagsForEntityDeletion($entity);

            foreach ($newDirtyFlags as $dirtyFlag) {
                $this->dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
            }

            // the source entities referring to the deleted entity. the
            // database may delete them, or set their reference to null,
            // without the ORM knowing

            $this->addReferencedEntity(
                $sourceHandler->getAssociationDependenciesForDeletion(),
                $entity,
            );
        }

        // process updates

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $changeSet = $unitOfWork->getEntityChangeSet($entity);

            // the original values are used to flag the partition the entity
            // was in, if the partitioning key is modified

            $originalValues = array_map(
                static fn(mixed $change): mixed => \is_array($change) ? $change[0] : null,
                $changeSet,
            );

            $sourceHandler = $this->handlerFactory->getSource($entity);

            $newDirtyFlags = $sourceHandler->generateDirtyFlagsForEntityModification(
                entity: $entity,
                modifiedProperties: array_keys($changeSet),
                originalValues: $originalValues,
            );

            foreach ($newDirtyFlags as $dirtyFlag) {
                $this->dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
            }

            // the source entities of other summaries that read the modified
            // properties through an association

            $this->addReferencedEntity(
                $sourceHandler->getAssociationDependenciesForModification(
                    array_keys($changeSet),
                ),
                $entity,
            );
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

        // process the source entities referring to the modified or deleted
        // related entities. the database still has the state before the
        // flush, so the references to the deleted entities are still there.
        // the source entities whose references are modified in this flush
        // are processed above, as updates

        foreach ($this->generateDirtyFlagsForReferencedEntities() as $dirtyFlag) {
            $this->dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
        }

        // persist and compute changesets

        $classMetadata = $entityManager->getClassMetadata(DirtyFlag::class);

        foreach ($this->dirtyFlags as $dirtyFlag) {
            $entityManager->persist($dirtyFlag);
            $unitOfWork->computeChangeSet($classMetadata, $dirtyFlag);
        }
    }

    /**
     * @param list<AssociationDependency> $dependencies
     */
    private function addReferencedEntity(array $dependencies, object $entity): void
    {
        foreach ($dependencies as $dependency) {
            $key = $dependency->getSummaryClass() . '|' . $dependency->getReversePath();

            $this->referencedEntities[$key]['dependency'] = $dependency;
            $this->referencedEntities[$key]['entities'][spl_object_id($entity)] = $entity;
        }
    }

    /**
     * Runs one query for each summary and path, for all the related entities
     * of the flush.
     *
     * @return iterable<DirtyFlag>
     */
    private function generateDirtyFlagsForReferencedEntities(): iterable
    {
        foreach ($this->referencedEntities as ['dependency' => $dependency, 'entities' => $entities]) {
            $summaryHandler = $this->handlerFactory
                ->getSummary($dependency->getSummaryClass());

            $partitions = $summaryHandler
                ->getSource()
                ->getLowestPartitionsReferringTo(
                    reversePath: $dependency->getReversePath(),
                    entities: array_values($entities),
                );

            foreach ($partitions as $partition) {
                yield $summaryHandler
                    ->getDirtyFlags()
                    ->createDirtyFlagForPartition($partition);
            }
        }

        $this->referencedEntities = [];
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

            $this->handlerFactory
                ->getSummary($dirtyFlag->getClass())
                ->getDirtyFlags()
                ->insertDirtyFlag($dirtyFlag);
            $this->dirtyFlags[$signature] = $dirtyFlag;
        }
    }

    /**
     * Dispatch dirty flags in postFlush because the flush might fail and we
     * don't want to dispatch the event if the flush fails.
     */
    public function postFlush(PostFlushEventArgs $doctrineEvent): void
    {
        // the state is cleared before dispatching, in case a listener flushes
        // the entity manager again

        $dirtyFlags = $this->dirtyFlags;
        $this->reset();

        $summaryClassses = [];

        foreach ($dirtyFlags as $dirtyFlag) {
            $event = new NewDirtyFlagEvent($dirtyFlag);
            $summaryClassses[$dirtyFlag->getClass()] = true;
            $this->eventDispatcher?->dispatch($event);
        }

        foreach (array_keys($summaryClassses) as $summaryClass) {
            $event = new DirtySummaryEvent($summaryClass);
            $this->eventDispatcher?->dispatch($event);
        }
    }
}
