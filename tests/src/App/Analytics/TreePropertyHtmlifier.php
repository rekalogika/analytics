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

namespace Rekalogika\Analytics\Tests\App\Analytics;

use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Contracts\Serialization\TupleSerializer;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\HtmlifierAware;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;
use Rekalogika\Analytics\PivotTable\Model\Tree\TreeMember;
use Rekalogika\Analytics\Tests\App\Serializer\TupleDtoSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TreePropertyHtmlifier implements Htmlifier, HtmlifierAware
{
    private ?Htmlifier $htmlifier = null;

    public function __construct(
        private TupleSerializer $tupleSerializer,
        private TupleDtoSerializer $tupleDtoSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    private function getHtmlifier(): Htmlifier
    {
        if (null === $this->htmlifier) {
            throw new \LogicException('Htmlifier is not set.');
        }

        return $this->htmlifier;
    }

    #[\Override]
    public function withHtmlifier(Htmlifier $htmlifier): static
    {
        if ($this->htmlifier === $htmlifier) {
            return $this;
        }

        $self = clone $this;
        $self->htmlifier = $htmlifier;

        return $self;
    }

    #[\Override]
    public function toHtml(mixed $input): string
    {
        if (!$input instanceof TreeMember) {
            throw new ValueNotSupportedException();
        }

        $node = $input->getNode();
        $tuple = $node->getTuple();

        $tupleDto = $this->tupleSerializer->serialize($tuple);
        $string = $this->tupleDtoSerializer->serialize($tupleDto);

        $url = $this->urlGenerator->generate(
            'tuple',
            ['data' => $string],
        );

        $content = $this->getHtmlifier()->toHtml($input->getContent());

        $node = $input->getNode();

        if (
            $node instanceof TreeNode
            && $node->getMember() instanceof MeasureMember
        ) {
            return $content;
        }

        return \sprintf(
            '<a href="%s" target="_blank">%s</a>',
            htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
            $content,
        );
    }
}
