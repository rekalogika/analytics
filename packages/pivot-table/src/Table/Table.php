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

namespace Rekalogika\PivotTable\Table;

final readonly class Table implements \Countable
{
    public function __construct(
        private Rows $header,
        private Rows $body,
        private Rows $footer,
    ) {}

    #[\Override]
    public function count(): int
    {
        return $this->header->count() + $this->body->count() + $this->footer->count();
    }

    public function getHeader(): Rows
    {
        return $this->header;
    }

    public function getBody(): Rows
    {
        return $this->body;
    }

    public function getFooter(): Rows
    {
        return $this->footer;
    }
}
