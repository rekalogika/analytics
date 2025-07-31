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

namespace Rekalogika\Analytics\PivotTable\Adapter\Tree;

use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\PivotTable\Contracts\Tree\SubtotalNode;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SubtotalAdapter implements SubtotalNode, TranslatableInterface
{
    public function __construct(
        private Measure $measure,
    ) {}

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $message = new TranslatableMessage('All');

        return $message->trans($translator, $locale);
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->measure->getValue();
    }

    #[\Override]
    public function getKey(): string
    {
        return '@values';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->measure->getLabel();
    }

    /**
     * @todo maybe replace with a MeasureMember?
     */
    #[\Override]
    public function getItem(): mixed
    {
        return $this->measure->getLabel();
    }
}
