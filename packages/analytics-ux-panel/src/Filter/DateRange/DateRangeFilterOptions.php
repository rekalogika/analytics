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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @template-covariant T of object
 */
interface DateRangeFilterOptions
{
    public function getLabel(): TranslatableInterface;

    public function getHelp(): ?TranslatableInterface;

    /**
     * @return T
     */
    public function transformDateToObject(\DateTimeInterface $date): object;
}
