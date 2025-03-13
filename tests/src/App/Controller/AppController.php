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
use Rekalogika\Analytics\DistinctValuesResolver;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    public function __construct(
        private readonly SummaryManagerRegistry $summaryManagerRegistry,
    ) {}


    #[Route('/', name: 'index')]
    public function index(
        #[MapQueryParameter()]
        ?string $parameters,
        PivotAwareSummaryQueryFactory $pivotAwareSummaryQueryFactory,
        AnalyticsChartBuilder $summaryChartBuilder,
        PivotTableRenderer $pivotTableRenderer,
    ): Response {

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
            ->getManager(OrderSummary::class);

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
            $chart = $summaryChartBuilder->createChart($result);
        } catch (UnsupportedData $e) {
            $chart = null;
        }

        return $this->render('app/index.html.twig', [
            'query' => $query,
            'pivotTable' => $pivotTable,
            'chart' => $chart,
        ]);
    }

    /**
     * Dummy controller to prevent services in arguments from being removed
     */
    public function dummy(DistinctValuesResolver $distinctValuesResolver): Response
    {
        return new Response();
    }
}
