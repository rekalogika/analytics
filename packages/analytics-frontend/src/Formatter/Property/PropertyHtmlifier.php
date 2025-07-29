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

namespace Rekalogika\Analytics\Frontend\Formatter\Property;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\HtmlifierAware;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;
use Rekalogika\Analytics\PivotTable\Model\Property;

final readonly class PropertyHtmlifier implements Htmlifier, HtmlifierAware
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

        return new self($htmlifier);
    }

    private function getHtmlifier(): Htmlifier
    {
        return $this->htmlifier ?? throw new LogicException('Htmlifier is not set.');
    }

    #[\Override]
    public function toHtml(mixed $input): string
    {
        if (!$input instanceof Property) {
            throw new ValueNotSupportedException();
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        return $this->getHtmlifier()->toHtml($content);
    }
}
