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

namespace Rekalogika\Analytics\Bundle\Formatter\Implementation;

use Rekalogika\Analytics\Bundle\Formatter\BackendHtmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;
use Rekalogika\Analytics\Bundle\Formatter\HtmlifierAware;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;

final readonly class ChainHtmlifier implements Htmlifier
{
    /**
     * @var list<BackendHtmlifier>
     */
    private array $backendHtmlifiers;

    /**
     * @param iterable<BackendHtmlifier> $backendHtmlifiers
     */
    public function __construct(
        iterable $backendHtmlifiers,
        private Stringifier $stringifier,
    ) {
        $newBackendHtmlifiers = [];

        foreach ($backendHtmlifiers as $backendHtmlifier) {
            if ($backendHtmlifier instanceof HtmlifierAware) {
                $backendHtmlifier = $backendHtmlifier->withHtmlifier($this);
            }

            $newBackendHtmlifiers[] = $backendHtmlifier;
        }

        $this->backendHtmlifiers = $newBackendHtmlifiers;
    }

    #[\Override]
    public function toHtml(mixed $input): string
    {
        foreach ($this->backendHtmlifiers as $htmlifier) {
            $result = $htmlifier->toHtml($input);

            if ($result !== null) {
                return $result;
            }
        }

        $result = $this->stringifier->toString($input);

        return htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
    }
}
