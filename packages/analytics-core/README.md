# rekalogika/analytics-core

The core of [Rekalogika Analytics](https://github.com/rekalogika/analytics).
Creates and maintains pre-aggregated summary tables for Doctrine entities, and
lets you query them as an OLAP data cube.

> [!WARNING]
> This package is still in development and not yet ready for production use.
> The API is subject to change.

## What's Inside

* **Metadata.** Attributes for defining a summary entity: `Summary`,
  `Dimension`, `Measure`, and `Partition`, in
  `Rekalogika\Analytics\Metadata\Attribute`.
* **Value resolvers.** Describe how a value is obtained from the source entity,
  such as `PropertyValue`, `IdentifierValue`, `IntegerValue`, and
  `CustomExpression`.
* **Aggregate functions.** `Sum`, `Count`, `Min`, `Max`, `Average`, `Range`,
  `StdDev`, and `SumSquare`.
* **Partitioning.** `DefaultIntegerPartition` for integer primary keys and
  `UuidV7IntegerPartition` for UUIDv7 primary keys. Partitions allow the
  summary table to be refreshed incrementally.
* **Time dimensions.** Hierarchical time dimensions (Gregorian and ISO week
  calendars, from year down to hour) with source and summary time zone
  support.
* **PostgreSQL extras.** Approximate distinct count using the
  [`hll`](https://github.com/citusdata/postgresql-hll) extension.
* **Refresh engine.** Tracks changes to the source entities and refreshes the
  affected partitions of the summary table.
* **Query engine.** The `SummaryManager` and `Query` API, returning results as
  a `CubeCell` that you can slice, dice, roll up, drill down, and pivot.

## Requirements

* PHP 8.2 or later
* Doctrine ORM 2.20+ or 3.x
* PostgreSQL

## Installation

This package is not meant to be used on its own. Only usage under Symfony is
supported for now, so install the Symfony bundle instead, which pulls in this
package:

```bash
composer require rekalogika/analytics-bundle
```

## Usage

Define a summary entity for your source entity:

```php
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\Core\AggregateFunction\Count;
use Rekalogika\Analytics\Core\AggregateFunction\Sum;
use Rekalogika\Analytics\Core\Entity\BaseSummary;
use Rekalogika\Analytics\Core\Partition\DefaultIntegerPartition;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity()]
#[Analytics\Summary(
    sourceClass: Order::class,
    label: new TranslatableMessage('Orders'),
)]
class OrderSummary extends BaseSummary
{
    #[ORM\Embedded()]
    #[Analytics\Partition(new IntegerValue('id'))]
    private DefaultIntegerPartition $partition;

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

Then query it using the `SummaryManager` service:

```php
use Rekalogika\Analytics\Contracts\SummaryManager;

/** @var SummaryManager $summaryManager */

$result = $summaryManager
    ->createQuery()
    ->from(OrderSummary::class)
    ->withDimensions('customerCountry')
    ->getResult();

foreach ($result->drillDown('customerCountry') as $cell) {
    $country = $cell->getCoordinates()->get('customerCountry')?->getMember();
    $count = $cell->getMeasures()->get('count')?->getValue();
}
```

## Documentation

[rekalogika.dev/analytics](https://rekalogika.dev/analytics)

## License

MIT

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/analytics`](https://github.com/rekalogika/analytics) monorepo.
