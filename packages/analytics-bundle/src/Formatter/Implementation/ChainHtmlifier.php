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
use Rekalogika\Analytics\Bundle\Formatter\BackendStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;

final readonly class ChainHtmlifier implements Htmlifier
{
    /**
     * @param iterable<BackendHtmlifier> $backendHtmlifiers
     * @param iterable<BackendStringifier> $backendStringifiers
     */
    public function __construct(
        private iterable $backendHtmlifiers,
        private iterable $backendStringifiers,
    ) {}

    #[\Override]
    public function toHtml(
        mixed $input,
        ?string $summaryClass = null,
        ?string $property = null,
    ): string {
        foreach ($this->backendHtmlifiers as $htmlifier) {
            $result = $htmlifier->toHtml($input);

            if ($result !== null) {
                return $result;
            }
        }

        foreach ($this->backendStringifiers as $stringifier) {
            $result = $stringifier->toString($input);

            if ($result !== null) {
                return htmlspecialchars($result);
            }
        }

        return get_debug_type($input);
    }
}
