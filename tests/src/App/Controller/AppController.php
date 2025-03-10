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

use Rekalogika\Analytics\Bundle\UI\PivotAwareSummaryQueryFactory;
use Rekalogika\Analytics\DistinctValuesResolver;
use Rekalogika\Analytics\PivotTableAdapter\PivotTableAdapter;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Misc\PivotTableRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    public function __construct(
        private readonly SummaryManagerRegistry $summaryManagerRegistry,
        private readonly PivotTableRenderer $pivotTableRenderer,
    ) {}


    #[Route('/app', name: 'app_app')]
    public function index(
        #[MapQueryParameter()]
        ?string $parameters,
        PivotAwareSummaryQueryFactory $pivotAwareSummaryQueryFactory,
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

        // dump($parameters);

        /** @var array<string,mixed> $parameters */

        $summaryTableManager = $this->summaryManagerRegistry
            ->getManager(OrderSummary::class);

        $query = $summaryTableManager->createQuery();
        $query = $pivotAwareSummaryQueryFactory->createFromParameters($query, $parameters);

        $result = new PivotTableAdapter($query->getResult()->getTree());
        $result = $this->pivotTableRenderer
            ->render($result, $query->getPivotedDimensions());

        return $this->render('app/index.html.twig', [
            'query' => $query,
            'result' => $result,
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
