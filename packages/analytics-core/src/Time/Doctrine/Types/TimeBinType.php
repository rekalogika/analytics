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

namespace Rekalogika\Analytics\Time\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Rekalogika\Analytics\Contracts\Model\TimeBin;
use Rekalogika\Analytics\Exception\ConversionException;

abstract class TimeBinType extends Type
{
    /**
     * @return class-string<TimeBin>
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

        if ($value instanceof $class) {
            /** @var TimeBin $value */
            return $value->getDatabaseValue();
        }

        throw new ConversionException(\sprintf(
            'The value must be an instance of "%s".',
            $class,
        ));
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
