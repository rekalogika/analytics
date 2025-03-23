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

namespace Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Formatter;

use Rekalogika\Analytics\Bundle\Formatter\BackendHtmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;
use Rekalogika\Analytics\Bundle\Formatter\HtmlifierAware;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeWrapper;

final readonly class NodeWrapperHtmlifier implements BackendHtmlifier, HtmlifierAware
{
    public function __construct(
        private ?Htmlifier $htmlifier = null,
    ) {}

    #[\Override]
    public function withHtmlifier(Htmlifier $htmlifier): static
    {
        if ($this->htmlifier === $htmlifier) {
            return $this;
        }

        return new static($htmlifier);
    }

    #[\Override]
    public function toHtml(mixed $input): ?string
    {
        if (!$input instanceof NodeWrapper) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        if (\is_string($content)) {
            return $content;
        }

        if ($this->htmlifier === null) {
            throw new \LogicException('Htmlifier is not set.');
        }

        return $this->htmlifier->toHtml($content);
    }
}
