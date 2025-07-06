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

namespace Rekalogika\Analytics\Tests\App\Controller;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Rekalogika\Analytics\Common\Exception\OverflowException;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Frontend\Chart\AnalyticsChartBuilder;
use Rekalogika\Analytics\Frontend\Chart\UnsupportedData;
use Rekalogika\Analytics\Frontend\Html\ExpressionRenderer;
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Rekalogika\Analytics\Frontend\Spreadsheet\SpreadsheetRenderer;
use Rekalogika\Analytics\Tests\App\Service\SummaryClassRegistry;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    public function __construct(
        private readonly SummaryManager $summaryManager,
        private readonly SummaryClassRegistry $summaryClassRegistry,
    ) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('app/index.html.twig', [
            'class_hashes' => $this->summaryClassRegistry->getHashToLabel(),
        ]);
    }


    #[Route('/summary/page/{hash}', name: 'summary')]
    public function summary(
        #[MapQueryParameter()]
        ?string $parameters,
        PivotAwareQueryFactory $pivotAwareQueryFactory,
        AnalyticsChartBuilder $chartBuilder,
        TableRenderer $htmlRenderer,
        ExpressionRenderer $expressionHtmlRenderer,
        string $hash,
    ): Response {
        $class = $this->summaryClassRegistry->getClassFromHash($hash);

        if ($parameters === null) {
            $parameters = [];
        } else {
            /** @psalm-suppress MixedAssignment */
            $parameters = json_decode($parameters, true);
        }

        if (!\is_array($parameters)) {
            $parameters = [];
        }

        /** @var array<string,mixed> $parameters */


        // populate query from url parameter
        $origQuery = $this->summaryManager->createQuery()->from($class);
        $query = $pivotAwareQueryFactory->createFromParameters($origQuery, $parameters);

        $result = $query->getResult();

        // create pivot table
        try {
            $pivotTable = $htmlRenderer->render(
                result: $result,
                pivotedDimensions: $query->getPivotedDimensions(),
            );

            $pivotTableError = null;
        } catch (OverflowException $e) {
            $pivotTable = null;
            $pivotTableError = 'The pivot table is too large to be displayed. Please refine your query parameters.';
        }

        // expression rendering
        $expressions = $expressionHtmlRenderer->renderExpression($origQuery);

        // create chart
        try {
            $chart = $chartBuilder->createChart($result);
            $chartError = null;
        } catch (UnsupportedData) {
            $chart = null;
            $chartError = null;
        } catch (\Throwable $e) {
            $chart = null;
            $chartError = 'An error occurred while creating the chart: ' . $e->getMessage();
        }

        return $this->render('app/summary.html.twig', [
            'title' => $result->getLabel(),
            'class_hashes' => $this->summaryClassRegistry->getHashToLabel(),
            'query' => $query,
            'pivotTable' => $pivotTable,
            'pivotTableError' => $pivotTableError,
            'chart' => $chart,
            'chartError' => $chartError,
            'expressions' => $expressions,
            'hash' => $hash,
        ]);
    }

    #[Route('/summary/download/{hash}', name: 'download')]
    public function download(
        #[MapQueryParameter()]
        ?string $parameters,
        PivotAwareQueryFactory $pivotAwareQueryFactory,
        SpreadsheetRenderer $spreadsheetRenderer,
        string $hash,
    ): Response {
        $class = $this->summaryClassRegistry->getClassFromHash($hash);

        if ($parameters === null) {
            $parameters = [];
        } else {
            /** @psalm-suppress MixedAssignment */
            $parameters = json_decode($parameters, true);
        }

        if (!\is_array($parameters)) {
            $parameters = [];
        }

        /** @var array<string,mixed> $parameters */

        // populate query from url parameter
        $query = $this->summaryManager->createQuery()->from($class);
        $query = $pivotAwareQueryFactory->createFromParameters($query, $parameters);

        $result = $query->getResult();

        // create pivot table
        $spreadsheet = $spreadsheetRenderer->render(result: $result);

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(
            function () use ($writer): void {
                $writer->save('php://output');
            },
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="spreadsheet.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Dummy controller to prevent services in arguments from being removed
     */
    public function dummy(DistinctValuesResolver $distinctValuesResolver): Response
    {
        return new Response();
    }
}
