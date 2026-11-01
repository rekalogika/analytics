# rekalogika/analytics-frontend

Renders the query results of
[Rekalogika Analytics](https://github.com/rekalogika/analytics) as pivot
tables, charts, and spreadsheets.

> [!WARNING]
> This package is still in development and not yet ready for production use.
> The API is subject to change.

## What's Inside

* **`TableRenderer`.** Renders a result as an HTML pivot table or a flat
  table, using a customizable Twig theme. A plain theme and a Bootstrap 5 theme
  are included.
* **`ChartGenerator`.** Creates a [Symfony UX Chart.js](https://symfony.com/bundles/ux-chartjs/current/index.html)
  chart from a result, choosing a suitable chart type automatically, or using
  the one you specify.
* **`SpreadsheetRenderer`.** Converts a result into a
  [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) `Spreadsheet`,
  which you can save as XLSX or other formats.
* **`PredicateRenderer`.** Renders the active filters of a query in a
  human-readable form.
* **Formatters.** Extensible `Stringifier`, `Htmlifier`, `Numberifier`, and
  `Cellifier` services that convert dimension members and measure values into
  their output representations, plus the `analytics_to_html` and
  `analytics_to_string` Twig filters.

## Installation

```bash
composer require rekalogika/analytics-frontend
```

If [`rekalogika/analytics-bundle`](https://github.com/rekalogika/analytics-bundle)
is installed, the services are registered automatically.

## Usage

```php
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Frontend\Chart\ChartGenerator;
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Rekalogika\Analytics\Frontend\Spreadsheet\SpreadsheetRenderer;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @var SummaryManager $summaryManager
 * @var TableRenderer $tableRenderer
 * @var ChartGenerator $chartGenerator
 * @var SpreadsheetRenderer $spreadsheetRenderer
 */

$cube = $summaryManager
    ->createQuery()
    ->from(OrderSummary::class)
    ->withDimensions('time.civil.year', 'customerCountry')
    ->getResult();

// HTML pivot table, with years as rows, and countries and measures as columns
$html = $tableRenderer->renderPivotTable(
    cube: $cube,
    measures: ['price', 'count'],
    rows: ['time.civil.year'],
    columns: ['customerCountry', '@values'],
);

// Chart.js chart, render it in Twig using render_chart()
$chart = $chartGenerator->createChart(
    cube: $cube,
    dimensions: ['time.civil.year', 'customerCountry'],
    measures: ['price'],
);

// spreadsheet
$spreadsheet = $spreadsheetRenderer->render(
    cell: $cube,
    dimensions: ['time.civil.year', 'customerCountry'],
    measures: ['price', 'count'],
);

(new Xlsx($spreadsheet))->save('orders.xlsx');
```

`@values` is a pseudo-dimension representing the measures. Its position
determines where the measures are laid out in the pivot table.

## Documentation

[rekalogika.dev/analytics](https://rekalogika.dev/analytics)

## License

MIT

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/analytics`](https://github.com/rekalogika/analytics) monorepo.
