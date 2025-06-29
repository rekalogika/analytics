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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\Choice;

/**
 * @template T
 */
interface ChoiceFilterOptions
{
    /**
     * @return iterable<string,T>
     */
    public function getChoices(): iterable;

    /**
     * @return T|null
     */
    public function getValueFromId(string $id): mixed;
}
