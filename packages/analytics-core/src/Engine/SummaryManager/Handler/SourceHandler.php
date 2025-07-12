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

namespace Rekalogika\Analytics\Engine\SummaryManager\Handler;

use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Metadata\Source\SourceMetadata;

/**
 * Represents a source class
 */
final readonly class SourceHandler
{
    public function __construct(
        private SourceMetadata $sourceMetadata,
        private HandlerFactory $handlerFactory,
    ) {}

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceMetadata->getClass();
    }

    /**
     * @return iterable<DirtyFlag>
     */
    public function generateDirtyFlagsForEntityCreation(object $entity): iterable
    {
        $summaryClasses = $this->sourceMetadata->getAllInvolvedSummaryClasses();

        foreach ($summaryClasses as $summaryClass) {
            yield new DirtyFlag(
                class: $summaryClass,
                level: null,
                key: null,
            );
        }
    }

    /**
     * @return iterable<DirtyFlag>
     */
    public function generateDirtyFlagsForEntityDeletion(object $entity): iterable
    {
        $summaryClasses = $this->sourceMetadata->getAllInvolvedSummaryClasses();

        foreach ($summaryClasses as $summaryClass) {
            $partitionHandler = $this->handlerFactory
                ->getSummary($summaryClass)
                ->getPartition();

            yield $partitionHandler->createDirtyFlagForSourceEntity($entity);
        }
    }

    /**
     * @param list<string> $modifiedProperties
     * @return iterable<DirtyFlag>
     */
    public function generateDirtyFlagsForEntityModification(
        object $entity,
        array $modifiedProperties,
    ): iterable {
        $summaryClasses = $this->sourceMetadata
            ->getInvolvedSummaryClassesByChangedProperties($modifiedProperties);

        foreach ($summaryClasses as $summaryClass) {
            $partitionHandler = $this->handlerFactory
                ->getSummary($summaryClass)
                ->getPartition();

            yield $partitionHandler->createDirtyFlagForSourceEntity($entity);
        }
    }
}
