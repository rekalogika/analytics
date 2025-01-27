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

use Rekalogika\Analytics\Bundle\Form\PivotAwareSummaryQuery;
use Rekalogika\Analytics\Bundle\Form\SummaryQueryType;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Misc\PivotTableRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    public function __construct(
        private readonly SummaryManagerRegistry $summaryManagerRegistry,
        private readonly PivotTableRenderer $pivotTableRenderer,
    ) {}


    #[Route('/app', name: 'app_app')]
    public function index(Request $request): Response
    {
        $summaryTableManager = $this->summaryManagerRegistry
            ->getManager(OrderSummary::class);

        $query = $summaryTableManager->createQuery();
        $query = new PivotAwareSummaryQuery($query);

        $form = $this->createForm(SummaryQueryType::class, $query);

        $form->handleRequest($request);

        $result = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->pivotTableRenderer
                ->render($query->getResult(), $query->getPivotedDimensions());
        }

        return $this->render('app/index.html.twig', [
            'form' => $form,
            'result' => $result,
        ]);
    }
}
