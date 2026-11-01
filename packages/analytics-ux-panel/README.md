# rekalogika/analytics-ux-panel

A [Symfony UX](https://ux.symfony.com/) user interface for
[Rekalogika Analytics](https://github.com/rekalogika/analytics). Lets users
modify the parameters of a pivot table interactively: choosing the dimensions
for the rows and columns, the measures to show, and the filters to apply.

> [!WARNING]
> This package is still in development and not yet ready for production use.
> The API is subject to change.

## What's Inside

* **`PivotAwareQueryFactory`.** Creates a `PivotAwareQuery` from a query and
  the parameters submitted by the panel. A `PivotAwareQuery` knows which
  dimensions go to the rows and columns, and which measures are selected.
* **The `analytics_render_control()` Twig function.** Renders the panel.
* **Filters.** Choice, date range, and number ranges filters. The filter for
  each dimension is determined by `FilterResolver` services. Enum dimensions
  get a choice filter out of the box, and you can implement your own
  `FilterResolver` for other types.
* **Stimulus controllers.** Drag and drop for arranging dimensions and
  measures, and controllers for the filters, built on
  [SortableJS](https://sortablejs.github.io/Sortable/),
  [Tom Select](https://tom-select.js.org/),
  [flatpickr](https://flatpickr.js.org/), and
  [Tippy.js](https://atomiks.github.io/tippyjs/).

## Requirements

* [`rekalogika/analytics-bundle`](https://github.com/rekalogika/analytics-bundle)
* Symfony StimulusBundle, with AssetMapper or Webpack Encore
* [Turbo](https://ux.symfony.com/turbo), as the panel uses Turbo Frames and
  Turbo Streams to update the output

## Installation

```bash
composer require rekalogika/analytics-ux-panel
```

If you don't use Symfony Flex, register the bundle manually:

```php
// config/bundles.php
return [
    // ...
    Rekalogika\Analytics\UX\PanelBundle\RekalogikaPanelBundle::class => ['all' => true],
];
```

With AssetMapper, install the JavaScript dependencies:

```bash
php bin/console importmap:install
```

## Usage

In the controller, create a `PivotAwareQuery` from the parameters sent by the
panel, then execute it and render the result, for example using
[`rekalogika/analytics-frontend`](https://github.com/rekalogika/analytics-frontend):

```php
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Rekalogika\Analytics\UX\PanelBundle\PivotAwareQueryFactory;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route('/orders')]
public function orders(
    #[MapQueryParameter()] ?string $parameters,
    SummaryManager $summaryManager,
    PivotAwareQueryFactory $pivotAwareQueryFactory,
    TableRenderer $tableRenderer,
): Response {
    $parameters = json_decode($parameters ?? '[]', true);

    $query = $summaryManager->createQuery()->from(OrderSummary::class);
    $query = $pivotAwareQueryFactory->createFromParameters($query, $parameters);

    $pivotTable = $tableRenderer->renderPivotTable(
        cube: $query->getResult(),
        measures: $query->getValues(),
        rows: $query->getRows(),
        columns: $query->getColumns(),
    );

    return $this->render('orders.html.twig', [
        'query' => $query,
        'pivotTable' => $pivotTable,
    ]);
}
```

In the template, render the panel and a target element for the output:

```twig
{{ analytics_render_control(
    query: query,
    output: pivotTable,
    target: 'pivottable',
    urlParameter: 'parameters',
) }}

<turbo-frame id="pivottable"></turbo-frame>
```

When the user changes the panel, the parameters are stored in the
`parameters` URL query parameter, and the output is updated in the `pivottable`
element.

## Documentation

[rekalogika.dev/analytics](https://rekalogika.dev/analytics)

## License

MIT

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/analytics`](https://github.com/rekalogika/analytics) monorepo.
