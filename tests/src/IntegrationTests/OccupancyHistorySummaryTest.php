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

use Doctrine\Common\Collections\Criteria;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\Exception\GroupingSetNotAvailable;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\OccupancyHistorySummary;
use Rekalogika\Analytics\Tests\App\Service\SummaryClassRegistry;
use Rekalogika\Analytics\Time\Bin\Gregorian\DayOfWeek;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * OccupancyHistorySummary stores only explicit grouping sets. A query for a
 * combination that is not stored must fail, and the demo user interface must
 * show the failure as a user-facing message instead of a server error.
 */
final class OccupancyHistorySummaryTest extends KernelTestCase
{
    private function getQuery(): Query
    {
        return static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OccupancyHistorySummary::class);
    }

    /**
     * @return iterable<string,array{list<string>}>
     */
    public static function provideAvailableDimensions(): iterable
    {
        yield 'grand total' => [[]];
        yield 'daily' => [['date', 'gender']];
        yield 'daily with day of week' => [['date', 'dayOfWeek']];
        yield 'day of week' => [['dayOfWeek']];
        yield 'day of month' => [['dayOfMonth', 'gender']];
        yield 'day of year' => [['dayOfYear']];
    }

    /**
     * @param list<string> $dimensions
     */
    #[DataProvider('provideAvailableDimensions')]
    public function testAvailableCombination(array $dimensions): void
    {
        $count = $this->getQuery()
            ->withDimensions(...$dimensions)
            ->getResult()
            ->getMeasures()
            ->get('count')
            ?->getRawValue();

        $this->assertIsNumeric($count);
        $this->assertGreaterThan(0, (int) $count);
    }

    public function testUnavailableCombination(): void
    {
        $this->expectException(GroupingSetNotAvailable::class);

        $this->getQuery()
            ->withDimensions('dayOfWeek', 'dayOfMonth')
            ->getResult();
    }

    public function testSummaryPageShowsUnavailableCombinationAsMessage(): void
    {
        $response = $this->request('summary', [
            'rows' => ['dayOfWeek', 'dayOfMonth'],
            'values' => ['count'],
        ]);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString(
            'This summary cannot combine these dimensions',
            (string) $response->getContent(),
        );
    }

    public function testSummaryPageShowsAvailableCombination(): void
    {
        $response = $this->request('summary', [
            'rows' => ['dayOfWeek', 'gender'],
            'values' => ['count'],
        ]);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringNotContainsString(
            'This summary cannot combine these dimensions',
            (string) $response->getContent(),
        );
    }

    public function testDownloadOfUnavailableCombinationIsBadRequest(): void
    {
        $response = $this->request('download', [
            'rows' => ['dayOfWeek', 'dayOfMonth'],
            'values' => ['count'],
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNumberRangeFilterWithInvalidTimeBin(): void
    {
        // 8 is not a day of week: on its own it matches nothing, as the
        // bound of a range it still works
        /** @psalm-suppress MixedAssignment */
        $count = static::getContainer()
            ->get(PivotAwareQueryFactory::class)
            ->createFromParameters($this->getQuery(), [
                'values' => ['count'],
                'filters' => ['dayOfWeek'],
                'filterExpressions' => ['dayOfWeek' => ['value' => '2,5-8,8']],
            ])
            ->getResult()
            ->getMeasures()
            ->get('count')
            ?->getRawValue();

        $expected = $this->getQuery()
            ->withDimensions()
            ->dice(Criteria::expr()->in('dayOfWeek', [
                DayOfWeek::Tuesday,
                DayOfWeek::Friday,
                DayOfWeek::Saturday,
                DayOfWeek::Sunday,
            ]))
            ->getResult()
            ->getMeasures()
            ->get('count')
            ?->getRawValue();

        $this->assertIsNumeric($expected);
        $this->assertGreaterThan(0, (int) $expected);
        $this->assertEquals($expected, $count);
    }

    /**
     * @param 'summary'|'download' $page
     * @param array<string,mixed> $parameters
     */
    private function request(string $page, array $parameters): Response
    {
        $kernel = static::bootKernel();

        $hash = static::getContainer()
            ->get(SummaryClassRegistry::class)
            ->getHashFromClass(OccupancyHistorySummary::class);

        $request = Request::create(
            \sprintf('/summary/%s/%s', $page === 'summary' ? 'page' : 'download', $hash),
            'GET',
            ['parameters' => json_encode($parameters, \JSON_THROW_ON_ERROR)],
        );

        return $kernel->handle($request);
    }
}
