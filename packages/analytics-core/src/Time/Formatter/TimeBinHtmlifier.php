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

namespace Rekalogika\Analytics\Time\Formatter;

use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;
use Rekalogika\Analytics\Time\HasTitle;
use Rekalogika\Analytics\Time\TimeBin;

final readonly class TimeBinHtmlifier implements Htmlifier
{
    public function __construct(
        private Stringifier $stringifier,
    ) {}

    #[\Override]
    public function toHtml(mixed $input): string
    {
        if (!$input instanceof HasTitle || !$input instanceof TimeBin) {
            throw new ValueNotSupportedException();
        }

        return \sprintf(
            '<span title="%s">%s</span>',
            $this->stringifier->toString($input->getTitle()),
            $this->stringifier->toString($input),
        );
    }
}
