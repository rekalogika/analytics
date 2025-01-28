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

namespace Rekalogika\Analytics\Bundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

final readonly class OnFlushListener
{
    // public function __construct(
    //     private SummaryMetadataFactory $summaryMetadataFactory,
    // ) {}

    // public function onFlush(OnFlushEventArgs $event): void
    // {
    //     $entityManager = $event->getObjectManager();

    //     if (!$entityManager instanceof EntityManagerInterface) {
    //         return;
    //     }

    //     $unitOfWork = $entityManager->getUnitOfWork();
    // }
}
