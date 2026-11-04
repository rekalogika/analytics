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

namespace Rekalogika\Analytics\Engine\SummaryVerifier;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final readonly class SummaryVerifierFactory
{
    public function __construct(
        private HandlerFactory $handlerFactory,
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
    ) {}

    /**
     * @param class-string $class
     */
    public function createSummaryVerifier(string $class): SummaryVerifier
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new UnexpectedValueException(\sprintf(
                'The class "%s" is not managed by the entity manager.',
                $class,
            ));
        }

        return new SummaryVerifier(
            entityManager: $entityManager,
            summaryMetadata: $this->metadataFactory->getSummaryMetadata($class),
            summaryHandler: $this->handlerFactory->getSummary($class),
        );
    }
}
