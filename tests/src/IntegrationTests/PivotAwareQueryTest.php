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

namespace Rekalogika\Analytics\Tests\IntegrationTests;

use Doctrine\Common\Collections\Expr\Comparison;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\Choice;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\ChoiceFilter;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQuery;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

final class PivotAwareQueryTest extends KernelTestCase
{
    /**
     * @param array<string,mixed> $parameters
     */
    private function createPivotAwareQuery(array $parameters): PivotAwareQuery
    {
        $query = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class);

        return static::getContainer()
            ->get(PivotAwareQueryFactory::class)
            ->createFromParameters($query, $parameters);
    }

    private function getFilter(PivotAwareQuery $query, string $dimension): Filter
    {
        foreach ($query->getFilterExpressions() as $name => $filter) {
            if ($name === $dimension) {
                return $filter;
            }
        }

        $this->fail(\sprintf('Filter "%s" not found', $dimension));
    }

    private function getChoiceFilter(PivotAwareQuery $query, string $dimension): ChoiceFilter
    {
        $filter = $this->getFilter($query, $dimension);
        $this->assertInstanceOf(ChoiceFilter::class, $filter);

        return $filter;
    }

    public function testChoiceFilterIgnoresUnknownId(): void
    {
        $query = $this->createPivotAwareQuery([
            'filters' => ['customerCountry'],
            'filterExpressions' => [
                'customerCountry' => ['values' => ['999999999']],
            ],
        ]);

        $filter = $this->getChoiceFilter($query, 'customerCountry');

        $this->assertSame([], $filter->getValues());
        $this->assertNull($filter->createExpression());

        foreach ($filter->getChoices() as $choice) {
            $this->assertFalse($choice->isSelected());
        }
    }

    public function testRenderedControlHasStablePerPanelIds(): void
    {
        $query = $this->createPivotAwareQuery([]);

        $twig = static::getContainer()->get('twig');
        $this->assertInstanceOf(Environment::class, $twig);

        $template = $twig->createTemplate('{{ analytics_render_control(query: query, urlParameter: urlParameter, target: "target") }}');

        $render = static fn(string $urlParameter): string => $template
            ->render(['query' => $query, 'urlParameter' => $urlParameter]);

        $first = $render('parameters');

        $this->assertSame($first, $render('parameters'));
        $this->assertStringContainsString('<turbo-frame id="analytics-panel-parameters-frame">', $first);
        $this->assertStringContainsString('<turbo-frame id="analytics-panel-parameters-filters"', $first);
        $this->assertStringContainsString('target="analytics-panel-parameters-filters"', $first);
        $this->assertStringContainsString('-frame-value="analytics-panel-parameters-frame"', $first);

        $second = $render('other.panel');

        $this->assertStringContainsString('<turbo-frame id="analytics-panel-other_2epanel-frame">', $second);
        $this->assertStringNotContainsString('analytics-panel-parameters', $second);
    }

    public function testChoiceFilterIgnoresMalformedValues(): void
    {
        $query = $this->createPivotAwareQuery([
            'filters' => ['customerCountry'],
        ]);

        $id = $this->getChoiceFilter($query, 'customerCountry')
            ->getChoices()[0]
            ->getId();

        $query = $this->createPivotAwareQuery([
            'filters' => ['customerCountry'],
            'filterExpressions' => [
                'customerCountry' => ['values' => [123, ['foo'], true, $id]],
            ],
        ]);

        $filter = $this->getChoiceFilter($query, 'customerCountry');

        $this->assertCount(1, $filter->getValues());
        $this->assertInstanceOf(Comparison::class, $filter->createExpression());
    }

    public function testChoiceFilterKeepsKnownIdsAndExplicitNull(): void
    {
        $query = $this->createPivotAwareQuery([
            'filters' => ['customerCountry'],
        ]);

        $id = $this->getChoiceFilter($query, 'customerCountry')
            ->getChoices()[0]
            ->getId();

        $query = $this->createPivotAwareQuery([
            'filters' => ['customerCountry'],
            'filterExpressions' => [
                'customerCountry' => ['values' => [$id, '999999999']],
            ],
        ]);

        $filter = $this->getChoiceFilter($query, 'customerCountry');
        $this->assertCount(1, $filter->getValues());
        $this->assertNotNull($filter->getValues()[0]);

        $expression = $filter->createExpression();
        $this->assertInstanceOf(Comparison::class, $expression);
        $this->assertNotContains(null, (array) $expression->getValue()->getValue());

        $query = $this->createPivotAwareQuery([
            'filters' => ['customerCountry'],
            'filterExpressions' => [
                'customerCountry' => ['values' => [Choice::NULL]],
            ],
        ]);

        $this->assertSame(
            [null],
            $this->getChoiceFilter($query, 'customerCountry')->getValues(),
        );
    }

    public function testUnknownAndHiddenItemsAreDropped(): void
    {
        $query = $this->createPivotAwareQuery([
            'rows' => ['foo', 'customerCountry', 'time'],
            'columns' => ['time.civil.month.month', 'bar', '@values'],
            'values' => ['count', 'baz', 'priceSumSquare', 'price'],
            'filters' => ['qux', 'customerGender', '@values'],
        ]);

        $this->assertSame(['customerCountry'], $query->getRows());
        $this->assertSame(['time.civil.month.month', '@values'], $query->getColumns());
        $this->assertSame(['count', 'price'], $query->getValues());
        $this->assertSame(['customerGender'], $query->getFilters());

        $this->assertNotNull($query->getResult()->getMeasures()->get('count'));
    }

    public function testDuplicateItemsAreDropped(): void
    {
        $query = $this->createPivotAwareQuery([
            'rows' => ['customerCountry', '@values', 'customerCountry'],
            'columns' => ['customerCountry', 'customerGender', '@values'],
            'values' => ['count', 'count'],
            'filters' => ['itemCategory', 'itemCategory'],
        ]);

        $this->assertSame(['customerCountry', '@values'], $query->getRows());
        $this->assertSame(['customerGender'], $query->getColumns());
        $this->assertSame(['count'], $query->getValues());
        $this->assertSame(['itemCategory'], $query->getFilters());
    }
}
