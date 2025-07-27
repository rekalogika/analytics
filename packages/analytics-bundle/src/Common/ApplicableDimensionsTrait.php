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

namespace Rekalogika\Analytics\Bundle\Common;

use Psr\Container\ContainerInterface;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;

/**
 * @template T of object
 */
trait ApplicableDimensionsTrait
{
    abstract private function getSpecificServiceLocator(): ContainerInterface;

    /**
     * @return iterable<T>
     */
    abstract private function getNonSpecificServices(): iterable;

    /**
     * @return class-string<T>
     */
    abstract private function getServiceClass(): string;

    /**
     * @param class-string $class The summary entity class name.
     * @return T|null
     */
    private function getSpecificService(
        string $class,
        string $dimension,
    ): ?object {
        $key = \sprintf('%s::%s', $class, $dimension);

        if (!$this->getSpecificServiceLocator()->has($key)) {
            return null;
        }

        $specificManager = $this->getSpecificServiceLocator()->get($key);

        if (!\is_object($specificManager)) {
            throw new InvalidArgumentException(\sprintf('Service "%s" is not an object', $key));
        }

        if (!is_a($specificManager, $this->getServiceClass())) {
            throw new InvalidArgumentException(\sprintf('Service "%s" is not a "%s"', $key, $this->getServiceClass()));
        }

        /** @var T */
        return $specificManager;
    }
}
