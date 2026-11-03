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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainHtmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Chain\ChainStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\DefaultStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\TranslatableStringifier;
use Rekalogika\Analytics\Frontend\Formatter\Twig\FormatterExtension;
use Rekalogika\Analytics\Frontend\Formatter\Twig\HtmlifierRuntime;
use Rekalogika\Analytics\Frontend\Formatter\Twig\StringifierRuntime;
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * The themes must work in a plain Twig environment, without the Symfony Twig
 * bridge.
 */
final class TableRendererThemeTest extends TestCase
{
    /**
     * @return iterable<string,array{string}>
     */
    public static function provideTheme(): iterable
    {
        yield 'base' => ['@RekalogikaAnalyticsFrontend/renderer.html.twig'];
        yield 'bootstrap 5' => ['@RekalogikaAnalyticsFrontend/bootstrap_5_renderer.html.twig'];
    }

    #[DataProvider('provideTheme')]
    public function testNoDataIsTranslated(string $theme): void
    {
        // A cell without children, the table is empty.
        $cell = $this->createStub(CubeCell::class);

        $html = $this->createRenderer($theme)->renderTable(
            cell: $cell,
            dimensions: ['country'],
            measures: ['count'],
            throwException: true,
        );

        $this->assertStringContainsString('[No result returned.]', $html);
    }

    public function testExceptionTitleIsTranslated(): void
    {
        $cell = $this->createStub(CubeCell::class);
        $cell->method('drillDown')->willThrowException(new \RuntimeException('boom'));

        $html = $this->createRenderer('@RekalogikaAnalyticsFrontend/bootstrap_5_renderer.html.twig')
            ->renderTable(
                cell: $cell,
                dimensions: ['country'],
                measures: ['count'],
            );

        $this->assertStringContainsString('[An error occurred]', $html);
    }

    private function createRenderer(string $theme): TableRenderer
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('getLocale')->willReturn('en');
        $translator->method('trans')->willReturnCallback(
            static fn(string $id): string => '[' . $id . ']',
        );

        $stringifier = new ChainStringifier([
            new TranslatableStringifier($translator),
            new DefaultStringifier(),
        ]);

        $htmlifier = new ChainHtmlifier(
            htmlifiers: [],
            stringifier: $stringifier,
        );

        $loader = new FilesystemLoader();
        $loader->addPath(
            __DIR__ . '/../../../packages/analytics-frontend/templates',
            'RekalogikaAnalyticsFrontend',
        );

        $twig = new Environment($loader, ['strict_variables' => true]);
        $twig->addExtension(new FormatterExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            StringifierRuntime::class => static fn(): StringifierRuntime => new StringifierRuntime($stringifier),
            HtmlifierRuntime::class => static fn(): HtmlifierRuntime => new HtmlifierRuntime($htmlifier),
        ]));

        return new TableRenderer($twig, $theme);
    }
}
