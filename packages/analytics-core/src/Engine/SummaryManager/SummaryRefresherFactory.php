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

namespace Rekalogika\Analytics\Engine\SummaryManager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\HandlerFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final readonly class SummaryRefresherFactory
{
    public function __construct(
        private HandlerFactory $handlerFactory,
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    /**
     * @param class-string $class
     */
    public function createSummaryRefresher(string $class): SummaryRefresher
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new UnexpectedValueException(\sprintf(
                'The class "%s" is not managed by the entity manager.',
                $class,
            ));
        }

        $metadata = $this->metadataFactory->getSummaryMetadata($class);

        return new SummaryRefresher(
            handlerFactory: $this->handlerFactory,
            entityManager: $entityManager,
            metadata: $metadata,
            eventDispatcher: $this->eventDispatcher,
        );
    }
}
