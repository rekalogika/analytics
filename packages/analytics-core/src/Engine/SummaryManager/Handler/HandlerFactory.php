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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Represents a summary class
 */
final class HandlerFactory implements ResetInterface
{
    /**
     * @var array<class-string,SummaryHandler>
     */
    private array $summaryHandlers = [];

    /**
     * @var array<class-string,SourceHandler>
     */
    private array $sourceHandlers = [];

    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly SourceMetadataFactory $sourceMetadataFactory,
        private readonly ManagerRegistry $managerRegistry,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {}

    #[\Override]
    public function reset(): void
    {
        foreach ($this->summaryHandlers as $handler) {
            $handler->reset();
        }
    }

    /**
     * @param class-string $class
     */
    private function getManager(string $class): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if ($manager === null) {
            throw new InvalidArgumentException(\sprintf(
                'No entity manager found for class "%s".',
                $class,
            ));
        }

        if (!$manager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException(\sprintf(
                'The entity manager for class "%s" is not an instance of EntityManagerInterface.',
                $class,
            ));
        }

        return $manager;
    }

    /**
     * @param class-string $summaryClass
     */
    public function getSummary(string $summaryClass): SummaryHandler
    {
        if (isset($this->summaryHandlers[$summaryClass])) {
            return $this->summaryHandlers[$summaryClass];
        }

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $manager = $this->getManager($summaryMetadata->getSummaryClass());

        return $this->summaryHandlers[$summaryClass] = new SummaryHandler(
            summaryMetadata: $summaryMetadata,
            entityManager: $manager,
            propertyAccessor: $this->propertyAccessor,
        );
    }

    /**
     * @param class-string|object $source
     */
    public function getSource(string|object $source): SourceHandler
    {
        if (\is_object($source)) {
            $source = $source::class;
        }

        if (isset($this->sourceHandlers[$source])) {
            return $this->sourceHandlers[$source];
        }

        $sourceMetadata = $this->sourceMetadataFactory
            ->getSourceMetadata($source);

        return $this->sourceHandlers[$source] = new SourceHandler(
            sourceMetadata: $sourceMetadata,
            handlerFactory: $this,
        );
    }
}
