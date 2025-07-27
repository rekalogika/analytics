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

use Doctrine\SqlFormatter\SqlFormatter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Rekalogika\Analytics\Contracts\MemberValuesManager;
use Rekalogika\Analytics\Contracts\Serialization\TupleSerializer;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Frontend\Chart\ChartGenerator;
use Rekalogika\Analytics\Frontend\Chart\UnsupportedData;
use Rekalogika\Analytics\Frontend\Exception\AnalyticsFrontendException;
use Rekalogika\Analytics\Frontend\Html\ExpressionRenderer;
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Rekalogika\Analytics\Frontend\Spreadsheet\SpreadsheetRenderer;
use Rekalogika\Analytics\Tests\App\Serializer\TupleDtoSerializer;
use Rekalogika\Analytics\Tests\App\Service\SummaryClassRegistry;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AppController extends AbstractController
{
    public function __construct(
        private readonly SummaryManager $summaryManager,
        private readonly SummaryClassRegistry $summaryClassRegistry,
        private readonly TranslatorInterface $translator,
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
        ChartGenerator $chartGenerator,
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
                throwException: true,
            );

            $pivotTableError = null;
        } catch (AnalyticsFrontendException $e) {
            $pivotTable = null;
            $pivotTableError = $e->trans($this->translator);
        }

        // expression rendering
        $expressions = $expressionHtmlRenderer->renderExpression($origQuery);

        // create chart
        try {
            $chart = $chartGenerator->createChart($result);
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

    #[Route('/summary/tuple/{data}', name: 'tuple')]
    public function tuple(
        string $data,
        TupleDtoSerializer $tupleDtoSerializer,
        TupleSerializer $tupleSerializer,
        #[Autowire('@rekalogika.analytics.summary_manager')]
        DefaultSummaryManager $summaryManager,
    ): Response {
        $sqlFormatter = new SqlFormatter();
        $tupleDto = $tupleDtoSerializer->deserialize($data);
        $row = $tupleSerializer->deserialize($tupleDto);
        $queryComponents = $summaryManager->getTupleQueryComponents($row);

        return $this->render('app/tuple.html.twig', [
            'row' => $row,
            'source_sql' => $sqlFormatter->format($queryComponents->getSqlStatement()),
            'class_hashes' => $this->summaryClassRegistry->getHashToLabel(),
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
    public function dummy(
        MemberValuesManager $memberValuesManager,
        ValueSerializer $valueSerializer,
        TupleSerializer $tupleSerializer,
    ): Response {
        return new Response();
    }
}
