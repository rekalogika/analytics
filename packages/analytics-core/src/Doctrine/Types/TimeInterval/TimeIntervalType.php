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

namespace Rekalogika\Analytics\Doctrine\Types\TimeInterval;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Rekalogika\Analytics\Contracts\Summary\ParameterTypeAware;
use Rekalogika\Analytics\Contracts\Summary\TimeInterval;
use Rekalogika\Analytics\Exception\ConversionException;

abstract class TimeIntervalType extends Type implements ParameterTypeAware
{
    /**
     * @return class-string<TimeInterval>
     */
    abstract protected function getClass(): string;

    #[\Override]
    abstract public function getSQLDeclaration(
        array $column,
        AbstractPlatform $platform,
    ): string;

    #[\Override]
    final public function convertToPHPValue(
        mixed $value,
        AbstractPlatform $platform,
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (!\is_int($value)) {
            throw new ConversionException(\sprintf(
                'The value must be an integer, "%s" given.',
                \gettype($value),
            ));
        }

        return ($this->getClass())::createFromDatabaseValue($value);
    }

    #[\Override]
    final public function convertToDatabaseValue(
        mixed $value,
        AbstractPlatform $platform,
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (\is_int($value)) {
            return $value;
        }

        $class = $this->getClass();

        if (\is_object($value) && is_a($value, $class, true)) {
            /** @var TimeInterval $value */
            return $value->getDatabaseValue();
        }

        throw new ConversionException(\sprintf(
            'The value must be an instance of "%s".',
            $class,
        ));
    }

    #[\Override]
    final public function getArrayParameterType(): ParameterType|ArrayParameterType|string|int|null
    {
        return ArrayParameterType::INTEGER;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
