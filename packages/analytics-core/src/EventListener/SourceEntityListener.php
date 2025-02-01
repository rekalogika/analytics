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
use Rekalogika\Analytics\Model\Entity\SummarySignal;
use Rekalogika\Analytics\SummaryManager\SignalGenerator;

final readonly class SourceEntityListener
{
    public function __construct(
        private SignalGenerator $signalGenerator,
        private NewEntitySignalListener $newEntitySignalListener,
    ) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();
        $signals = [];

        // process deletions

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $newSignals = $this->signalGenerator
                ->generateForEntityDeletion(entity: $entity);

            foreach ($newSignals as $signal) {
                $signals[$signal->getSignature()] = $signal;
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
                $signals[$signal->getSignature()] = $signal;
            }
        }

        // process inserts

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $newSignals = $this->signalGenerator
                ->generateForEntityCreation(entity: $entity);

            foreach ($newSignals as $signal) {
                $signals[$signal->getSignature()] = $signal;

                $this->newEntitySignalListener
                    ->collectClassToProcess($signal->getClass());
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
                    $signals[$signal->getSignature()] = $signal;
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

        foreach ($signals as $signal) {
            $entityManager->persist($signal);
            $unitOfWork->computeChangeSet($classMetadata, $signal);
        }
    }
}
