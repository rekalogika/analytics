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

use Rekalogika\Analytics\Bundle\Chart\AnalyticsChartBuilder;
use Rekalogika\Analytics\Bundle\Chart\UnsupportedData;
use Rekalogika\Analytics\Bundle\UI\PivotAwareSummaryQueryFactory;
use Rekalogika\Analytics\Bundle\UI\PivotTableRenderer;
use Rekalogika\Analytics\Bundle\UI\SpreadsheetRenderer;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\SummaryManagerRegistry;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Service\SummaryClassRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    public function __construct(
        private readonly SummaryManagerRegistry $summaryManagerRegistry,
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
        PivotAwareSummaryQueryFactory $pivotAwareSummaryQueryFactory,
        AnalyticsChartBuilder $chartBuilder,
        PivotTableRenderer $pivotTableRenderer,
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

        $summaryTableManager = $this->summaryManagerRegistry
            ->getManager($class);

        // populate query from url parameter
        $query = $summaryTableManager->createQuery();
        $query = $pivotAwareSummaryQueryFactory->createFromParameters($query, $parameters);
        $result = $query->getResult();

        // create pivot table
        $pivotTable = $pivotTableRenderer->createPivotTable(
            result: $result,
            pivotedDimensions: $query->getPivotedDimensions(),
        );

        // create chart
        try {
            $chart = $chartBuilder->createChart($result);
        } catch (UnsupportedData $e) {
            $chart = null;
        }

        return $this->render('app/summary.html.twig', [
            'class_hashes' => $this->summaryClassRegistry->getHashToLabel(),
            'query' => $query,
            'pivotTable' => $pivotTable,
            'chart' => $chart,
            'hash' => $hash,
        ]);
    }

    #[Route('/summary/download/{hash}', name: 'download')]
    public function download(
        #[MapQueryParameter()]
        ?string $parameters,
        PivotAwareSummaryQueryFactory $pivotAwareSummaryQueryFactory,
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

        $summaryTableManager = $this->summaryManagerRegistry
            ->getManager($class);

        // populate query from url parameter
        $query = $summaryTableManager->createQuery();
        $query = $pivotAwareSummaryQueryFactory->createFromParameters($query, $parameters);
        $result = $query->getResult();

        // create pivot table
        $spreadsheet = $spreadsheetRenderer->createSpreadsheet(
            result: $result,
            pivotedDimensions: $query->getPivotedDimensions(),
        );

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $response = new StreamedResponse(
            function () use ($writer) {
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

    // #[Route('/test', name: 'test')]
    // public function someTest(): Response
    // {
    //     $summaryTableManager = $this->summaryManagerRegistry
    //         ->getManager(OrderSummary::class);

    //     // populate query from url parameter
    //     $query = $summaryTableManager->createQuery();

    //     $result = $query
    //         ->groupBy('time.year', 'time.monthOfYear')
    //         ->select('count')
    //         ->getResult()
    //         ->getTree();

    //     return new Response('<html><body>Test</body></html>');
    // }
}
