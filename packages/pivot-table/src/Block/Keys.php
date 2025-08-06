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

namespace Rekalogika\PivotTable\Block;

final readonly class Keys
{
    /**
     * @param list<string> $pivotedKeys
     * @param list<string> $unpivotedKeys
     * @param list<string> $currentKeyPath
     */
    public function __construct(
        private array $unpivotedKeys,
        private array $pivotedKeys,
        private array $currentKeyPath,
    ) {
        if (
            array_diff($this->pivotedKeys, $this->unpivotedKeys) !== $this->pivotedKeys
            || array_diff($this->unpivotedKeys, $this->pivotedKeys) !== $this->unpivotedKeys
        ) {
            throw new \InvalidArgumentException(
                'Pivoted nodes and unpivoted nodes must not overlap.',
            );
        }
    }

    //
    // keys
    //

    /**
     * @return list<string>
     */
    public function getKeys(): array
    {
        return array_merge($this->unpivotedKeys, $this->pivotedKeys);
    }

    /**
     * @return list<string>
     */
    public function getUnpivotedKeys(): array
    {
        return $this->unpivotedKeys;
    }

    /**
     * @return list<string>
     */
    public function getPivotedKeys(): array
    {
        return $this->pivotedKeys;
    }

    public function isKeyPivoted(string $key): bool
    {
        return \in_array($key, $this->pivotedKeys, true);
    }

    public function isKeyUnpivoted(string $key): bool
    {
        return \in_array($key, $this->unpivotedKeys, true);
    }

    public function getFirstPivotedKey(): ?string
    {
        return $this->pivotedKeys[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getCurrentKeyPath(): array
    {
        return $this->currentKeyPath;
    }

    public function getCurrentKey(): ?string
    {
        if (\count($this->currentKeyPath) === 0) {
            return null;
        }

        return $this->currentKeyPath[\count($this->currentKeyPath) - 1] ?? null;
    }

    /**
     * @param int<1,max> $level 1 means gets the next key, 2 means get the next
     * after the next key, and so on.
     * @return string|null
     */
    public function getNextKey(int $level = 1): ?string
    {
        $keys = $this->getKeys();
        $currentKey = $this->getCurrentKey();

        if ($currentKey === null) {
            return $keys[0] ?? null;
        }

        $currentIndex = array_search($currentKey, $keys, true);

        if ($currentIndex === false) {
            return null;
        }

        $nextIndex = $currentIndex + $level;

        return $keys[$nextIndex] ?? null;
    }
}
