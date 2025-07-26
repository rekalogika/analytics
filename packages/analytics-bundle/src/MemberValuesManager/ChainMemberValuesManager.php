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

namespace Rekalogika\Analytics\Bundle\MemberValuesManager;

use Psr\Container\ContainerInterface;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\MemberValuesManager;

final readonly class ChainMemberValuesManager implements MemberValuesManager
{
    /**
     * @param ContainerInterface $specificManagerLocator Services that supplies
     * the information about the classes that they handle. i.e. their
     * `getApplicableDimensions()` method returns an iterable.
     * @param iterable<MemberValuesManager> $nonSpecificManager Services
     * that do not supply the information about the classes that they handle.
     * i.e. their `getApplicableDimensions()` method returns null.
     */
    public function __construct(
        private ContainerInterface $specificManagerLocator,
        private iterable $nonSpecificManager,
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

        if ($this->specificManagerLocator->has($key)) {
            $specificManager = $this->specificManagerLocator->get($key);

            if (!$specificManager instanceof MemberValuesManager) {
                throw new InvalidArgumentException(\sprintf('Service "%s" is not a "%s".', $key, MemberValuesManager::class));
            }

            $result = $specificManager->getDistinctValues($class, $dimension, $limit);

            if ($result === null) {
                throw new InvalidArgumentException(\sprintf('Service "%s" returned null', $key));
            }

            return $result;
        }

        foreach ($this->nonSpecificManager as $resolver) {
            $values = $resolver->getDistinctValues($class, $dimension, $limit);

            if ($values !== null) {
                return $values;
            }
        }

        return null;
    }

    #[\Override]
    public function getValueFromIdentifier(
        string $class,
        string $dimension,
        string $id,
    ): mixed {
        $specificManager = $this->getSpecificManager($class, $dimension);

        if ($specificManager !== null) {
            return $specificManager->getValueFromIdentifier($class, $dimension, $id);
        }

        foreach ($this->nonSpecificManager as $manager) {
            $value = $manager->getValueFromIdentifier($class, $dimension, $id);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }


    #[\Override]
    public function getIdentifierFromValue(
        string $class,
        string $dimension,
        mixed $value,
    ): ?string {
        $specificManager = $this->getSpecificManager($class, $dimension);

        if ($specificManager !== null) {
            return $specificManager->getIdentifierFromValue($class, $dimension, $value);
        }

        foreach ($this->nonSpecificManager as $manager) {
            $id = $manager->getIdentifierFromValue($class, $dimension, $value);

            if ($id !== null) {
                return $id;
            }
        }

        return null;
    }


    /**
     * @param class-string $class The summary entity class name.
     */
    private function getSpecificManager(
        string $class,
        string $dimension,
    ): ?MemberValuesManager {
        $key = \sprintf('%s::%s', $class, $dimension);

        if (!$this->specificManagerLocator->has($key)) {
            return null;
        }

        $specificManager = $this->specificManagerLocator->get($key);

        if (!$specificManager instanceof MemberValuesManager) {
            throw new InvalidArgumentException(\sprintf('Service "%s" is not a "%s"', $key, MemberValuesManager::class));
        }

        return $specificManager;
    }
}
