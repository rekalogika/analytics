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

namespace Rekalogika\Analytics\Metadata\Util;

/**
 * Converts the property paths declared by value resolvers and aggregate
 * functions to the keys Doctrine uses in the change set of the source entity.
 */
final readonly class InvolvedPropertyUtil
{
    private function __construct() {}

    /**
     * Returns the change set keys that indicate a change to the value read
     * by the path. The accepted path shapes are the ones the path resolver
     * accepts:
     *
     * - `prop`: `prop`
     * - `assoc.prop`, `assoc(Class).prop`, `assoc.*`: `assoc`, a change to
     *   the related entity itself is not reported in the change set of the
     *   source entity
     * - `embedded.prop`: `embedded.prop`, which is how Doctrine reports a
     *   change to a field of an embeddable. Without the Doctrine mapping, it
     *   cannot be distinguished from `assoc.prop`, so both `embedded` and
     *   `embedded.prop` are returned. The extra key never matches a change
     *   set key, it is harmless.
     * - `(Class).prop`: the same as `prop`. The root entity is cast to a
     *   subclass, and the change set of the subclass reports `prop`
     * - `*`, `(Class).*`: nothing, the identity and type of an entity do not
     *   change
     *
     * @return list<string>
     */
    public static function getChangeSetKeys(string $path): array
    {
        $segments = array_map(trim(...), explode('.', trim($path)));

        // root entity cast, e.g. `(Class).prop`

        if (str_starts_with($segments[0], '(')) {
            array_shift($segments);
        }

        $first = $segments[0] ?? '';

        if ($first === '' || $first === '*') {
            return [];
        }

        // cast of an association, e.g. `assoc(Class).prop`

        $castPosition = strpos($first, '(');

        if ($castPosition !== false) {
            $first = trim(substr($first, 0, $castPosition));

            return $first === '' ? [] : [$first];
        }

        if (\count($segments) === 1) {
            return [$first];
        }

        // a cast or an asterisk after the first segment means the first
        // segment is an association, only associations can be cast or
        // aliased

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '*' || str_contains($segment, '(')) {
                return [$first];
            }
        }

        return [$first, implode('.', $segments)];
    }
}
