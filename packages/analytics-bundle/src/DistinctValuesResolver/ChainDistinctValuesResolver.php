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
use Rekalogika\Analytics\DistinctValuesResolver;

final class ChainDistinctValuesResolver implements DistinctValuesResolver
{
    /**
     * @param iterable<DistinctValuesResolver> $nonSpecificResolvers
     */
    public function __construct(
        private ContainerInterface $specificResolverLocator,
        private iterable $nonSpecificResolvers,
    ) {}

    #[\Override]
    public static function getApplicableDimensions(): ?iterable
    {
        return null;
    }

    #[\Override]
    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): ?iterable {
        $key = \sprintf('%s::%s', $class, $dimension);

        if ($this->specificResolverLocator->has($key)) {
            $specificResolver = $this->specificResolverLocator->get($key);

            if (!$specificResolver instanceof DistinctValuesResolver) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" is not a DistinctValuesResolver', $key));
            }

            $result = $specificResolver->getDistinctValues($class, $dimension, $limit);

            if ($result === null) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" returned null', $key));
            }

            return $result;
        }

        foreach ($this->nonSpecificResolvers as $resolver) {
            $values = $resolver->getDistinctValues($class, $dimension, $limit);

            if ($values !== null) {
                return $values;
            }
        }

        return null;
    }

    #[\Override]
    public function getValueFromId(
        string $class,
        string $dimension,
        string $id,
    ): mixed {
        $key = \sprintf('%s::%s', $class, $dimension);

        if ($this->specificResolverLocator->has($key)) {
            $specificResolver = $this->specificResolverLocator->get($key);

            if (!$specificResolver instanceof DistinctValuesResolver) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" is not a DistinctValuesResolver', $key));
            }

            return $specificResolver->getValueFromId($class, $dimension, $id);
        }

        foreach ($this->nonSpecificResolvers as $resolver) {
            $value = $resolver->getValueFromId($class, $dimension, $id);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }
}
