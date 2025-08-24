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

namespace Rekalogika\Analytics\Bundle\Serialization;

use Psr\Container\ContainerInterface;
use Rekalogika\Analytics\Bundle\Common\ApplicableDimensionsTrait;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Serialization\UnsupportedValue;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;

final readonly class ChainValueSerializer implements ValueSerializer
{
    /**
     * @use ApplicableDimensionsTrait<ValueSerializer>
     */
    use ApplicableDimensionsTrait;

    /**
     * @param ContainerInterface $specificServiceLocator Services that supplies
     * the information about the classes that they handle. i.e. their
     * `getApplicableDimensions()` method returns an iterable.
     * @param iterable<ValueSerializer> $nonSpecificServices Services
     * that do not supply the information about the classes that they handle.
     * i.e. their `getApplicableDimensions()` method returns null.
     */
    public function __construct(
        private ContainerInterface $specificServiceLocator,
        private iterable $nonSpecificServices,
    ) {}

    #[\Override]
    private function getSpecificServiceLocator(): ContainerInterface
    {
        return $this->specificServiceLocator;
    }

    #[\Override]
    private function getNonSpecificServices(): iterable
    {
        return $this->nonSpecificServices;
    }

    #[\Override]
    private function getServiceClass(): string
    {
        return ValueSerializer::class;
    }


    #[\Override]
    public function deserialize(
        string $class,
        string $dimension,
        ?string $identifier,
    ): mixed {
        $specificService = $this->getSpecificService($class, $dimension);

        if ($specificService !== null) {
            return $specificService->deserialize($class, $dimension, $identifier);
        }

        foreach ($this->getNonSpecificServices() as $manager) {
            try {
                return $manager->deserialize($class, $dimension, $identifier);
            } catch (UnsupportedValue) {
            }
        }

        throw new InvalidArgumentException(\sprintf(
            'No serializer found for class "%s", dimension "%s", and identifier "%s". You may need to create a custom implementation of "%s" for this class and dimension.',
            $class,
            $dimension,
            $identifier ?? 'null',
            ValueSerializer::class,
        ));
    }


    #[\Override]
    public function serialize(
        string $class,
        string $dimension,
        mixed $value,
    ): ?string {
        $specificManager = $this->getSpecificService($class, $dimension);

        if ($specificManager !== null) {
            return $specificManager->serialize($class, $dimension, $value);
        }

        foreach ($this->getNonSpecificServices() as $manager) {
            try {
                return $manager->serialize($class, $dimension, $value);
            } catch (UnsupportedValue) {
            }
        }

        throw new InvalidArgumentException(\sprintf(
            'No serializer found for class "%s", dimension "%s", and value "%s". You may need to create a custom implementation of "%s" for this class and dimension.',
            $class,
            $dimension,
            get_debug_type($value),
            ValueSerializer::class,
        ));
    }
}
