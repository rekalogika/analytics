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

namespace Rekalogika\Analytics\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Model\Entity\SummarySignal;
use Rekalogika\Analytics\SummaryManager\Event\NewSignalEvent;
use Rekalogika\Analytics\SummaryManager\SignalGenerator;
use Symfony\Contracts\Service\ResetInterface;

final class SourceEntityListener implements ResetInterface
{
    /**
     * @var array<string,SummarySignal>
     */
    private array $signals = [];

    public function __construct(
        private readonly SignalGenerator $signalGenerator,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function reset()
    {
        $this->signals = [];
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
            $newSignals = $this->signalGenerator
                ->generateForEntityDeletion(entity: $entity);

            foreach ($newSignals as $signal) {
                $this->signals[$signal->getSignature()] = $signal;
            }
        }

        // process updates

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $changedProperties = array_keys($unitOfWork->getEntityChangeSet($entity));

            $newSignals = $this->signalGenerator
                ->generateForEntityModification(
                    entity: $entity,
                    modifiedProperties: $changedProperties,
                );

            foreach ($newSignals as $signal) {
                $this->signals[$signal->getSignature()] = $signal;
            }
        }

        // process inserts

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $newSignals = $this->signalGenerator
                ->generateForEntityCreation(entity: $entity);

            foreach ($newSignals as $signal) {
                $this->signals[$signal->getSignature()] = $signal;
            }
        }

        // process collection deletions

        foreach ($unitOfWork->getScheduledCollectionDeletions() as $collection) {
            // iterate of the collection, find the backRef to the owner. use the
            // relation as the 'changedProperties' argument

            $mapping = $collection->getMapping();
            $backRefFieldName = $mapping['mappedBy'];

            if ($backRefFieldName === null) {
                continue;
            }

            foreach ($collection as $entity) {
                $newSignals = $this->signalGenerator
                    ->generateForEntityModification(
                        entity: $entity,
                        modifiedProperties: [$backRefFieldName],
                    );

                foreach ($newSignals as $signal) {
                    $this->signals[$signal->getSignature()] = $signal;
                }
            }
        }

        // process collection updates

        // not necessary? because we only consider manyToOne and it is already
        // handled in the entity updates
        // foreach ($unitOfWork->getScheduledCollectionUpdates() as $collection) {
        // }

        // persist and compute changesets

        $classMetadata = $entityManager->getClassMetadata(SummarySignal::class);

        foreach ($this->signals as $signal) {
            $entityManager->persist($signal);
            $unitOfWork->computeChangeSet($classMetadata, $signal);
        }
    }

    /**
     * Dispatch signals in postFlush because the flush might fail and we don't
     * want to dispatch the event if the flush fails.
     */
    public function postFlush(PostFlushEventArgs $doctrineEvent): void
    {
        foreach ($this->signals as $signal) {
            $event = new NewSignalEvent($signal);
            $this->eventDispatcher?->dispatch($event);
        }

        $this->signals = [];
    }
}
