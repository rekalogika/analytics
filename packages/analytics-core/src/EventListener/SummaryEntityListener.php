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

use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

final readonly class SummaryEntityListener
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    public function prePersist(PrePersistEventArgs $event): void
    {
        if ($this->summaryMetadataFactory->isSummary($event->getObject()::class)) {
            throw new \LogicException('Summary entity is not allowed be persisted directly');
        }
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        if ($this->summaryMetadataFactory->isSummary($event->getObject()::class)) {
            throw new \LogicException('Summary entity is not allowed be updated directly');
        }
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        if ($this->summaryMetadataFactory->isSummary($event->getObject()::class)) {
            throw new \LogicException('Summary entity is not allowed be removed directly');
        }
    }

    public function postLoad(PostLoadEventArgs $event): void
    {
        if ($this->summaryMetadataFactory->isSummary($event->getObject()::class)) {
            throw new \LogicException('Summary entity is not allowed be loaded directly');
        }
    }
}
