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

namespace Rekalogika\Analytics\Frontend\Formatter\Chain;

use Rekalogika\Analytics\Frontend\Formatter\Exception\HtmlifierFailureException;
use Rekalogika\Analytics\Frontend\Formatter\Exception\StringifierFailureException;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\HtmlifierAware;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;

final readonly class ChainHtmlifier implements Htmlifier
{
    /**
     * @var list<Htmlifier>
     */
    private array $htmlifiers;

    /**
     * @param iterable<Htmlifier> $htmlifiers
     */
    public function __construct(
        iterable $htmlifiers,
        private Stringifier $stringifier,
    ) {
        $newHtmlifiers = [];

        foreach ($htmlifiers as $htmlifier) {
            if ($htmlifier instanceof HtmlifierAware) {
                $htmlifier = $htmlifier->withHtmlifier($this);
            }

            $newHtmlifiers[] = $htmlifier;
        }

        $this->htmlifiers = $newHtmlifiers;
    }

    #[\Override]
    public function toHtml(mixed $input): string
    {
        foreach ($this->htmlifiers as $htmlifier) {
            try {
                return $htmlifier->toHtml($input);
            } catch (ValueNotSupportedException) {
            }
        }

        try {
            $result = $this->stringifier->toString($input);

            return htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        } catch (StringifierFailureException) {
            throw new HtmlifierFailureException($input);
        }
    }
}
