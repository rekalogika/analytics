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

namespace Rekalogika\Analytics\Bundle\DistinctValuesResolver;

use Psr\Container\ContainerInterface;
use Rekalogika\Analytics\Bundle\Common\ApplicableDimensionsTrait;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;

final readonly class ChainDistinctValuesResolver implements DistinctValuesResolver
{
    /**
     * @use ApplicableDimensionsTrait<DistinctValuesResolver>
     */
    use ApplicableDimensionsTrait;

    /**
     * @param ContainerInterface $specificServiceLocator Services that supplies
     * the information about the classes that they handle. i.e. their
     * `getApplicableDimensions()` method returns an iterable.
     * @param iterable<DistinctValuesResolver> $nonSpecificServices Services
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
        return DistinctValuesResolver::class;
    }

    #[\Override]
    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): ?iterable {
        $specificService = $this->getSpecificService($class, $dimension);

        if ($specificService !== null) {
            return $specificService->getDistinctValues($class, $dimension, $limit);
        }

        foreach ($this->getNonSpecificServices() as $resolver) {
            $values = $resolver->getDistinctValues($class, $dimension, $limit);

            if ($values !== null) {
                return $values;
            }
        }

        return null;
    }
}
