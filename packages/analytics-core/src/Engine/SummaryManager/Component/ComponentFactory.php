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

namespace Rekalogika\Analytics\Engine\SummaryManager\Component;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Represents a summary class
 */
final class ComponentFactory
{
    /**
     * @var array<class-string,SummaryComponent>
     */
    private array $summaryComponents = [];

    /**
     * @var array<class-string,SourceComponent>
     */
    private array $sourceComponents = [];

    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly SourceMetadataFactory $sourceMetadataFactory,
        private readonly ManagerRegistry $managerRegistry,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {}

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
    public function getSummary(string $summaryClass): SummaryComponent
    {
        if (isset($this->summaryComponents[$summaryClass])) {
            return $this->summaryComponents[$summaryClass];
        }

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $manager = $this->getManager($summaryMetadata->getSummaryClass());

        return $this->summaryComponents[$summaryClass] = new SummaryComponent(
            summaryMetadata: $summaryMetadata,
            entityManager: $manager,
            propertyAccessor: $this->propertyAccessor,
        );
    }

    /**
     * @param class-string $sourceClass
     */
    public function getSource(string $sourceClass): SourceComponent
    {
        if (isset($this->sourceComponents[$sourceClass])) {
            return $this->sourceComponents[$sourceClass];
        }

        $sourceMetadata = $this->sourceMetadataFactory
            ->getSourceMetadata($sourceClass);

        $manager = $this->getManager($sourceMetadata->getClass());

        return $this->sourceComponents[$sourceClass] = new SourceComponent(
            sourceMetadata: $sourceMetadata,
            entityManager: $manager,
        );
    }
}
