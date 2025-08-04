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

use Rekalogika\Analytics\Contracts\Serialization\TupleMapper;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\HtmlifierAware;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;
use Rekalogika\Analytics\PivotTable\Model\Table\MeasureValue;
use Rekalogika\Analytics\Tests\App\Service\SummaryClassRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MeasureValueHtmlifier implements Htmlifier, HtmlifierAware
{
    private ?Htmlifier $htmlifier = null;

    public function __construct(
        private TupleMapper $tupleMapper,
        private UrlGeneratorInterface $urlGenerator,
        private SummaryClassRegistry $summaryClassRegistry,
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
        if (!$input instanceof MeasureValue) {
            throw new ValueNotSupportedException();
        }

        $row = $input->getRow();
        $tuple = $row->getTuple();

        $tupleDto = $this->tupleMapper->toDto($tuple);
        $string = json_encode($tupleDto->toArray());
        $hash = $this->summaryClassRegistry->getHashFromClass($tuple->getSummaryClass());

        $url = $this->urlGenerator->generate(
            'tuple',
            [
                'data' => $string,
                'hash' => $hash,
            ],
        );

        $content = $this->getHtmlifier()->toHtml($input->getContent());

        return \sprintf(
            '<a href="%s" target="_blank">%s</a>',
            htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
            $content,
        );
    }
}
