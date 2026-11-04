# rekalogika/analytics

Creates and maintains pre-aggregated summary tables for Doctrine entities,
giving you a fast and easy way to run analytical (OLAP) queries directly inside
your Symfony application.

You describe a summary table as a Doctrine entity, annotated with dimensions
and measures. The framework rolls up the data from the source entity into the
summary table, keeps it up to date as the source changes, and lets you query it
as a data cube. The results can be rendered as pivot tables, charts, and
spreadsheets.

> [!WARNING]
> These packages are still in development and not yet ready for production
> use. Not all features are implemented yet, and the API is subject to change.

## Documentation

Full documentation is available at
[rekalogika.dev/analytics](https://rekalogika.dev/analytics).

## Demo

A self-contained demo image, including PostgreSQL, sample data, and
pre-populated summary tables:

```bash
docker run --rm -p 8123:8000 ghcr.io/rekalogika/analytics-demo
```

Then open <http://localhost:8123>.

To build it yourself from this repository:

```bash
docker build -f container/demo/Containerfile -t analytics-demo .
docker run --rm -p 8123:8000 analytics-demo
```

## Features

* Works directly on your Doctrine entities. No intermediate fact tables, no
  separate ETL process, and no external analytical system to keep in sync.
* Understands Doctrine metadata, so knowledge about your schema is not
  duplicated elsewhere.
* Detects when source entities are added, modified, or deleted, and refreshes
  only the affected partitions in the background using Symfony Messenger.
* Incremental, partition-based refreshes, debounced to avoid redundant work.
* Hierarchical dimensions, including time dimensions (year, quarter, month,
  week, day, hour, ISO week, and more) with time zone conversion.
* Built-in aggregate functions: `Sum`, `Count`, `Min`, `Max`, `Average`,
  `Range`, `StdDev`, and approximate distinct count using PostgreSQL HLL.
* Query results as a navigable data cube: slice, dice, roll up, drill down, and
  pivot.
* Renders results as HTML pivot tables, Chart.js charts, and spreadsheets
  (via PhpSpreadsheet).
* An optional Symfony UX panel that lets users pick dimensions, measures, and
  filters interactively.
* Keeps your data in your own database. Nothing is sent to an external
  service.

## Requirements

* PHP 8.2 or later
* Symfony 6.4, 7.x, or 8.x
* Doctrine ORM 2.20+ or 3.x
* PostgreSQL. Other databases are not supported yet. The
  [`hll`](https://github.com/citusdata/postgresql-hll) extension is required if
  you use approximate distinct counts.
* Symfony Messenger, for background refreshes

## Packages

This repository is a monorepo. The packages are split into separate
read-only repositories and published individually on Packagist.

| Package | Description |
| --- | --- |
| [`rekalogika/analytics-core`](packages/analytics-core) | The core framework: summary metadata, refresh engine, query engine, and the data cube API. |
| [`rekalogika/analytics-bundle`](packages/analytics-bundle) | Symfony bundle integrating the framework into a Symfony application. |
| [`rekalogika/analytics-frontend`](packages/analytics-frontend) | Renders query results as pivot tables, charts, and spreadsheets. |
| [`rekalogika/analytics-ux-panel`](packages/analytics-ux-panel) | Symfony UX user interface for choosing the parameters of a pivot table. |

## Installation

Install the bundle using Composer:

```bash
composer require rekalogika/analytics-bundle
```

Optionally, install the frontend and the UX panel:

```bash
composer require rekalogika/analytics-frontend rekalogika/analytics-ux-panel
```

Route the refresh messages to an asynchronous Messenger transport:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStartCommand': async
```

## Quick Example

Suppose you have an `Order` entity with an integer ID, a `time` property, a
`customer` relation, and an `item` relation that has a `price`. A summary
entity for it can look like this:

```php
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\AggregateFunction\Sum;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\Partition\DefaultIntegerPartition;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Rekalogika\Analytics\Time\Dimension\Group\TimeGroup;
use Rekalogika\Analytics\Time\Metadata\TimeProperties;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Order::class,
    label: new TranslatableMessage('Orders'),
)]
class OrderSummary extends BaseSummary
{
    // determines how the source records are grouped for incremental refreshes
    #[ORM\Embedded()]
    #[Analytics\Partition(new IntegerValue('id'))]
    private DefaultIntegerPartition $partition;

    #[ORM\Embedded()]
    #[Analytics\Dimension(
        source: new PropertyValue('time'),
        label: new TranslatableMessage('Time'),
    )]
    #[TimeProperties(
        sourceTimeZone: new \DateTimeZone('UTC'),
        summaryTimeZone: new \DateTimeZone('Asia/Jakarta'),
    )]
    private TimeGroup $time;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: true)]
    #[Analytics\Dimension(
        source: new IdentifierValue('customer.country'),
        label: new TranslatableMessage('Customer country'),
    )]
    private ?Country $customerCountry = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Sum('item.price'),
        label: new TranslatableMessage('Price'),
    )]
    private ?int $price = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Analytics\Measure(
        function: new Count('id'),
        label: new TranslatableMessage('Count'),
    )]
    private ?int $count = null;

    // getters...
}
```

Create the table using your usual Doctrine migration workflow, then populate it
for the first time:

```bash
php bin/console rekalogika:analytics:refresh:range 'App\Entity\OrderSummary'
```

From then on, changes to `Order` entities are picked up automatically and
refreshed in the background by the Messenger worker. So are changes to the
entities the summary reads through an association, e.g. the `Customer` of
`customer.country`, and their deletion, as long as they are made through the
ORM. Changes made otherwise, e.g. with a DQL `UPDATE`, native SQL, or a
database cascade from an entity not deleted through the ORM, can be reported
with `SummaryManager::markDirty()`.

To summarize only some of the source entities, add a `filter` to the
`Summary` attribute. It is a Doctrine Collections expression whose fields are
property paths of the source entity. A condition that cannot be written as a
comparison can be written in DQL with `SourceCondition`, with the property
paths in brackets:

```php
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Rekalogika\Analytics\Core\Filter\SourceCondition;

#[Analytics\Summary(
    sourceClass: Order::class,
    filter: new CompositeExpression(CompositeExpression::TYPE_AND, [
        new Comparison('status', '<>', 'cancelled'),
        new Comparison('customer.country.id', 'IN', ['ID', 'SG']),
        new SourceCondition('[customer.*] INSTANCE OF App\Entity\IndividualCustomer'),
    ]),
)]
```

The properties the filter reads are tracked like the ones of the dimensions
and measures: a change that moves a source entity in or out of the filter
refreshes the summary.

Query the summary using the `SummaryManager` service:

```php
use Doctrine\Common\Collections\Criteria;
use Rekalogika\Analytics\Contracts\SummaryManager;

/** @var SummaryManager $summaryManager */

$result = $summaryManager
    ->createQuery()
    ->from(OrderSummary::class)
    ->withDimensions('time.civil.year', 'customerCountry')
    ->dice(Criteria::expr()->orX(
        Criteria::expr()->eq('time.civil.year', 2023),
        Criteria::expr()->eq('time.civil.year', 2024),
    ))
    ->getResult();

// the number of orders in 2024
$count = $result
    ->slice('time.civil.year', 2024)
    ?->getMeasures()
    ->get('count')
    ?->getValue();

// iterate over each year
foreach ($result->drillDown('time.civil.year') as $cell) {
    $year = $cell->getCoordinates()->get('time.civil.year')?->getMember();
    $price = $cell->getMeasures()->get('price')?->getValue();
}
```

## Development

The test suite needs a PostgreSQL database with the `hll` extension. A
`compose.yml` file is provided that starts one, along with a Messenger worker.

```bash
composer install

# start the database and the messenger worker
make compose-up

# run the full test suite: static analysis, schema creation, fixtures,
# summary refresh, and PHPUnit
make test

# run the test application in a browser (requires the Symfony CLI)
make serve
```

Other useful targets:

* `make phpunit`: run PHPUnit only. Pass arguments using `c`, for example
  `make phpunit c="--filter QueryTest"`.
* `make phpstan`, `make psalm`: run static analysis.
* `make php-cs-fixer`, `make rector`: fix the coding style and apply
  refactorings.
* `make fixtures-load`: reload the test fixtures.
* `make summary-refreshrange`: populate the summary tables of the test
  application.
* `make js`: rebuild the JavaScript assets of the UX panel.

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/analytics`](https://github.com/rekalogika/analytics) monorepo.

## License

MIT. See [LICENSE](LICENSE).
