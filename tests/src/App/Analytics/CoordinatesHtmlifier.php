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

use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\ValueNotSupportedException;
use Rekalogika\Analytics\Tests\App\Service\SummaryClassRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CoordinatesHtmlifier implements Htmlifier
{
    public function __construct(
        private CoordinatesMapper $coordinatesMapper,
        private UrlGeneratorInterface $urlGenerator,
        private SummaryClassRegistry $summaryClassRegistry,
    ) {}

    #[\Override]
    public function toHtml(mixed $input): string
    {
        if (!$input instanceof Coordinates) {
            throw new ValueNotSupportedException();
        }

        $coordinatesDto = $this->coordinatesMapper->toDto($input);
        $string = json_encode($coordinatesDto);
        $hash = $this->summaryClassRegistry->getHashFromClass($input->getSummaryClass());

        $url = $this->urlGenerator->generate(
            'cell',
            [
                'data' => $string,
                'hash' => $hash,
            ],
        );

        return \sprintf(
            '<a href="%s" target="_blank">Debug Cell</a>',
            htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
        );
    }
}
