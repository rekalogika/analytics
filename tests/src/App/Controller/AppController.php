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

use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\Dto\CoordinatesDto;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Frontend\Chart\ChartGenerator;
use Rekalogika\Analytics\Frontend\Chart\UnsupportedData;
use Rekalogika\Analytics\Frontend\Exception\AnalyticsFrontendException;
use Rekalogika\Analytics\Frontend\Html\PredicateRenderer;
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Rekalogika\Analytics\Frontend\Spreadsheet\SpreadsheetRenderer;
use Rekalogika\Analytics\Tests\App\Service\SummaryClassRegistry;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        private readonly LoggerInterface $logger,
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
        PredicateRenderer $predicateRenderer,
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
        $values = $query->getValues();
        $rows = $query->getRows();
        $columns = $query->getColumns();
        $result = $query->getResult();

        // create pivot table
        try {
            $pivotTable = $htmlRenderer->renderPivotTable(
                cube: $result->getCube(),
                rows: $rows,
                columns: $columns,
                measures: $values,
                throwException: true,
            );

            $pivotTableError = null;
        } catch (AnalyticsFrontendException $e) {
            $pivotTable = null;
            $pivotTableError = $e->trans($this->translator);
        } catch (\Throwable $e) {
            $pivotTable = null;
            $pivotTableError = 'An error occurred while rendering the pivot table.';
            $this->logger->error(
                'An error occurred while rendering the pivot table.',
                [
                    'exception' => $e,
                ],
            );
        }

        // expression rendering
        $predicate = $predicateRenderer->renderPredicate($origQuery);

        // create chart
        try {
            if (\count($values) === 0) {
                $chart = null;
            } else {
                $dimensions = array_values(array_filter(
                    array_merge($rows, $columns),
                    static fn(string $field): bool => $field !== '@values',
                ));

                if ($dimensions === []) {
                    $chart = null;
                } else {
                    $chart = $chartGenerator->createChart(
                        cube: $result->getCube(),
                        dimensions: $dimensions,
                        measures: $values,
                    );
                }
            }

            $chartError = null;
        } catch (UnsupportedData) {
            $chart = null;
            $chartError = null;
        } catch (AnalyticsFrontendException $e) {
            $chart = null;
            $chartError = $e->trans($this->translator);
        } catch (\Throwable $e) {
            $chart = null;
            $chartError = 'An error occurred while creating the chart: ';
            $this->logger->error(
                'An error occurred while creating the chart.',
                [
                    'exception' => $e,
                ],
            );
        }

        return $this->render('app/summary.html.twig', [
            'title' => $result->getLabel(),
            'class_hashes' => $this->summaryClassRegistry->getHashToLabel(),
            'query' => $query,
            'pivotTable' => $pivotTable,
            'pivotTableError' => $pivotTableError,
            'chart' => $chart,
            'chartError' => $chartError,
            'predicate' => $predicate,
            'hash' => $hash,
        ]);
    }

    #[Route('/summary/cell/{hash}/{data}', name: 'cell')]
    public function cell(
        string $hash,
        string $data,
        CoordinatesMapper $coordinatesMapper,
        PredicateRenderer $predicateRenderer,
    ): Response {
        $class = $this->summaryClassRegistry->getClassFromHash($hash);
        $sqlFormatter = new SqlFormatter(new HtmlHighlighter(usePre: false));

        /**
         * @psalm-suppress MixedArgument
         * @phpstan-ignore argument.type
         */
        $coordinatesDto = CoordinatesDto::fromArray(json_decode($data, true));
        $cell = $coordinatesMapper->fromDto($class, $coordinatesDto);

        if (!$cell instanceof DefaultCell) {
            throw new UnexpectedValueException(\sprintf(
                'Expected %s, got %s',
                DefaultCell::class,
                get_debug_type($cell),
            ));
        }

        $queryComponents = $cell->getSourceQueryComponents();

        $sourceSql = $queryComponents->getInterpolatedSqlStatement() . ';';
        $sourceSql = $sqlFormatter->compress($sourceSql);
        $sourceSql = $sqlFormatter->highlight($sourceSql);

        $predicate = $predicateRenderer
            ->renderPredicate($cell->getCoordinates());

        return $this->render('app/cell.html.twig', [
            'cell' => $cell,
            'predicate' => $predicate,
            'source_sql' => $sourceSql,
            'class_hashes' => $this->summaryClassRegistry->getHashToLabel(),
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
        $measures = $query->getValues();

        // create pivot table
        $spreadsheet = $spreadsheetRenderer->render($result->getTable(), $measures);

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
        DistinctValuesResolver $distinctValueResolver,
        ValueSerializer $valueSerializer,
        CoordinatesMapper $coordinatesMapper,
    ): Response {
        return new Response();
    }
}
