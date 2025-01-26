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

namespace Rekalogika\Analytics\Tests\App\Misc;

use Rekalogika\Analytics\PivotTable\TableRenderer;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Environment;

class PivotTableRenderer extends TableRenderer
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    private function renderBlock(string $block, mixed $data): string
    {
        return $this->twig
            ->load('app/renderer.html.twig')
            ->renderBlock($block, [
                'data' => $data,
            ]);
    }

    #[\Override]
    protected function renderNoData(): string
    {
        return $this->renderBlock('empty', null);
    }

    #[\Override]
    protected function getTableAttributes(): string
    {
        return 'class="table table-bordered"';
    }

    #[\Override]
    protected function getTableHeaderAttributes(): string
    {
        return 'class="table-light"';
    }

    #[\Override]
    protected function getTableBodyAttributes(): string
    {
        return 'class="table-group-divider"';
    }

    protected function renderNull(): string
    {
        return $this->renderBlock('null', null);
    }

    #[\Override]
    protected function renderContent(mixed $content): string
    {
        if ($content === null) {
            return $this->renderNull();
        } elseif ($content instanceof TranslatableInterface) {
            return $this->renderBlock('translatable', $content);
        } elseif ($content instanceof \BackedEnum) {
            return $this->renderBlock('backedenum', $content);
        } elseif (\is_bool($content)) {
            return $this->renderBlock('boolean', $content);
        }

        return parent::renderContent($content);
    }
}
