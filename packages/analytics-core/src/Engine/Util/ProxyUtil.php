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

namespace Rekalogika\Analytics\Engine\Util;

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;

final readonly class ProxyUtil
{
    private function __construct() {}

    /**
     * Normalize a class name that may be a proxy.
     *
     * @param class-string $className
     * @return class-string
     */
    public static function normalizeClassName(string $className): string
    {
        if (false !== $pos = strrpos($className, '\\__CG__\\')) {
            // Doctrine Common proxy marker
            $realClass = substr($className, $pos + 8);
        } elseif (false !== $pos = strrpos($className, '\\__PM__\\')) {
            // Ocramius Proxy Manager
            $className = ltrim($className, '\\');
            $rpos = strrpos($className, '\\');

            if (false === $rpos) {
                return $className;
            }

            $realClass = substr(
                $className,
                8 + $pos,
                $rpos - ($pos + 8),
            );
        } else {
            return $className;
        }

        if (!class_exists($realClass)) {
            throw new UnexpectedValueException(\sprintf(
                'Class "%s" is a proxy of "%s", but the real class does not exist.',
                $className,
                $realClass,
            ));
        }

        return $realClass;
    }
}
