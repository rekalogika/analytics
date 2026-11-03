# rekalogika/analytics-bundle

Symfony bundle for [Rekalogika Analytics](https://github.com/rekalogika/analytics).
Integrates the framework into a Symfony application, adding OLAP functionality
to your Doctrine entities.

> [!WARNING]
> This package is still in development and not yet ready for production use.
> The API is subject to change.

## What's Inside

* Service wiring for the metadata, refresh engine, and query engine from
  [`rekalogika/analytics-core`](https://github.com/rekalogika/analytics-core),
  including the `SummaryManager` service.
* A refresh agent that refreshes the summary tables in the background, using
  Symfony Messenger, whenever the source entities change.
* Console commands for maintaining the summary tables.
* Service wiring for
  [`rekalogika/analytics-frontend`](https://github.com/rekalogika/analytics-frontend),
  if it is installed.
* Translations.

## Requirements

* PHP 8.2 or later
* Symfony 6.4, 7.x, or 8.x
* Doctrine ORM 2.20+ or 3.x, with DoctrineBundle and DoctrineMigrationsBundle
* PostgreSQL
* Symfony Messenger and Symfony Lock

## Installation

```bash
composer require rekalogika/analytics-bundle
```

If you don't use Symfony Flex, register the bundle manually:

```php
// config/bundles.php
return [
    // ...
    Rekalogika\Analytics\Bundle\RekalogikaAnalyticsBundle::class => ['all' => true],
];
```

Route the refresh messages to an asynchronous Messenger transport, and make
sure a worker is consuming it:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStartCommand': async
```

## Configuration

The default configuration:

```yaml
# config/packages/rekalogika_analytics.yaml
rekalogika_analytics:
    # The maximum number of results returned from a query. A query exceeding
    # this limit throws an OverflowException.
    query_result_limit: 5000

    # The maximum number of nodes created to fill gaps between values. Going
    # over this limit throws an OverflowException.
    filling_nodes_limit: 10000

    # The Twig theme used to render pivot tables. It should extend
    # @RekalogikaAnalyticsFrontend/renderer.html.twig. A Bootstrap 5 theme is
    # available as @RekalogikaAnalyticsFrontend/bootstrap_5_renderer.html.twig.
    table_theme: '@RekalogikaAnalyticsFrontend/renderer.html.twig'

    # Determines when the refresh agent refreshes the dirty partitions. All
    # values are in seconds; null disables the setting.
    refresh:
        # A dirty partition is not refreshed until its oldest change is at
        # least this old.
        minimum_age: 600

        # A dirty partition is not refreshed until there have been no new
        # changes for this long.
        minimum_idle_delay: 300

        # A dirty partition is refreshed once its oldest change is this old,
        # ignoring minimum_idle_delay.
        maximum_age: 1800

        # An upper-level partition is refreshed immediately after the
        # partitions below it, unless it has been refreshed within this period.
        upper_partition_cooldown: 300
```

## Console Commands

| Command | Description |
| --- | --- |
| `rekalogika:analytics:refresh` | Immediately refreshes all the dirty partitions of a summary table. |
| `rekalogika:analytics:refresh:range` | Refreshes a range of source records, regardless of whether they are dirty. Usually run once after creating a summary table. Supports `--resume` and `--batchsize`. |
| `rekalogika:analytics:truncate` | Truncates a summary table. |
| `rekalogika:analytics:debug:summary` | Shows information about a summary table. |
| `rekalogika:analytics:debug:source` | Shows which changes to a source class refresh its summaries, and which paths through related entities are not tracked. |
| `rekalogika:analytics:uuid:summary-to-source` | Converts a UUIDv7 partition key in the summary table to the corresponding source value. |

## Documentation

[rekalogika.dev/analytics](https://rekalogika.dev/analytics)

## License

MIT

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/analytics`](https://github.com/rekalogika/analytics) monorepo.
