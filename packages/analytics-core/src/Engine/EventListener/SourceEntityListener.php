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

namespace Rekalogika\Analytics\Engine\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\SummaryManager\Event\NewDirtyFlagEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\HandlerFactory;
use Symfony\Contracts\Service\ResetInterface;

final class SourceEntityListener implements ResetInterface
{
    /**
     * @var array<string,DirtyFlag>
     */
    private array $dirtyFlags = [];

    public function __construct(
        private readonly HandlerFactory $handlerFactory,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    #[\Override]
    public function reset(): void
    {
        $this->dirtyFlags = [];
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
     * Dispatch dirty flags in postFlush because the flush might fail and we
     * don't want to dispatch the event if the flush fails.
     */
    public function postFlush(PostFlushEventArgs $doctrineEvent): void
    {
        foreach ($this->dirtyFlags as $dirtyFlag) {
            $event = new NewDirtyFlagEvent($dirtyFlag);
            $this->eventDispatcher?->dispatch($event);
        }

        $this->dirtyFlags = [];
    }
}
